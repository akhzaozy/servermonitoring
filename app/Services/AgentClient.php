<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client untuk berkomunikasi dengan agent di server remote (/api/agent/*).
 */
class AgentClient
{
    protected function request(Server $server, int $timeout): PendingRequest
    {
        return Http::withToken((string) $server->token)
            ->acceptJson()
            ->timeout($timeout)
            ->connectTimeout(min($timeout, 4));
    }

    protected function url(Server $server, string $path): string
    {
        return rtrim((string) $server->base_url, '/').'/api/agent/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function metrics(Server $server): array
    {
        return $this->parse($server, fn () => $this->request($server, 6)->get($this->url($server, 'metrics')));
    }

    /**
     * @return array<string, mixed>
     */
    public function exec(Server $server, string $command, ?string $cwd): array
    {
        return $this->parse($server, fn () => $this->request($server, config('monitor.terminal.timeout') + 10)
            ->post($this->url($server, 'exec'), ['command' => $command, 'cwd' => $cwd]));
    }

    /**
     * Ambil metrik beberapa server sekaligus (paralel).
     *
     * @param  Collection<int, Server>  $servers
     * @return array<int, array{ok: bool, data?: array<string, mixed>, error?: string, latency_ms?: int}>
     */
    public function metricsMany(Collection $servers): array
    {
        if ($servers->isEmpty()) {
            return [];
        }

        $responses = Http::pool(fn (Pool $pool) => $servers->map(
            fn (Server $s) => $pool->as((string) $s->id)
                ->withToken((string) $s->token)->acceptJson()->timeout(5)->connectTimeout(3)
                ->get($this->url($s, 'metrics'))
        )->all());

        $result = [];
        foreach ($servers as $server) {
            $r = $responses[(string) $server->id] ?? null;
            if ($r instanceof Response && $r->successful()) {
                $result[$server->id] = [
                    'ok' => true,
                    'data' => $r->json() ?? [],
                    'latency_ms' => (int) round(($r->handlerStats()['total_time'] ?? 0) * 1000),
                ];
            } else {
                $result[$server->id] = [
                    'ok' => false,
                    'error' => $r instanceof Response ? $this->describe($r) : 'Server tidak dapat dihubungi',
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parse(Server $server, callable $call): array
    {
        if (! $server->base_url) {
            throw new RuntimeException('URL agent belum diisi.');
        }

        try {
            /** @var Response $response */
            $response = $call();
        } catch (ConnectionException $e) {
            throw new RuntimeException('Server tidak dapat dihubungi ('.$server->base_url.').');
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->describe($response));
        }

        return $response->json() ?? [];
    }

    protected function describe(Response $r): string
    {
        return match ($r->status()) {
            401, 403 => 'Token agent ditolak atau fitur dinonaktifkan di server tujuan.',
            404 => 'Endpoint agent tidak ditemukan. Pastikan URL benar.',
            503 => 'Agent belum dikonfigurasi (AGENT_TOKEN kosong).',
            default => 'Agent membalas HTTP '.$r->status().'.',
        };
    }
}

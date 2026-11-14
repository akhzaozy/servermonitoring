<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\AgentClient;
use App\Services\SshServerService;
use App\Services\StbMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerApiController extends Controller
{
    public function __construct(
        protected AgentClient $agent,
        protected SshServerService $ssh,
        protected StbMonitorService $stb,
    ) {}

    public function index(): JsonResponse
    {
        Server::local();

        return response()->json(
            Server::orderByRaw("type = 'local' desc")->orderBy('id')->get()->map(fn (Server $s) => $this->present($s))
        );
    }

    /**
     * Ringkasan semua server (status + CPU/RAM/Disk) untuk dashboard & halaman Servers.
     */
    public function overview(): JsonResponse
    {
        $local = Server::local();
        $others = Server::where('type', '!=', 'local')->where('is_active', true)->orderBy('id')->get();

        $httpRemotes = $others->where('type', 'remote')->values();
        $remoteResults = $this->agent->metricsMany($httpRemotes);

        $rows = [$this->summary($local, ['ok' => true, 'data' => $this->stb->getSystemMetrics(), 'latency_ms' => 0])];

        foreach ($others as $server) {
            if ($server->isSsh()) {
                $t0 = microtime(true);
                try {
                    $m = $this->ssh->metrics($server);
                    $res = [
                        'ok' => true,
                        'data' => $m,
                        'latency_ms' => (int) round((microtime(true) - $t0) * 1000),
                    ];
                } catch (\Throwable $e) {
                    $res = ['ok' => false, 'error' => $e->getMessage()];
                }
            } else {
                $res = $remoteResults[$server->id] ?? ['ok' => false, 'error' => 'Offline'];
            }

            if ($res['ok']) {
                $this->stb->recordSnapshot($res['data'], $server->id);
            }
            $rows[] = $this->summary($server, $res);
        }

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type', $request->filled('base_url') ? 'remote' : 'ssh');
        if (! in_array($type, ['ssh', 'remote'], true)) {
            $type = 'remote';
        }

        if ($type === 'ssh') {
            $data = $request->validate([
                'name' => 'required|string|max:80',
                'ssh_host' => 'required|string|max:255',
                'ssh_port' => 'nullable|integer|between:1,65535',
                'ssh_user' => 'required|string|max:100',
                'ssh_password' => 'nullable|string|max:500',
                'ssh_key' => 'nullable|string|max:16000',
                'use_cloudflare' => 'nullable|boolean',
                'portainer_url' => 'nullable|url|max:255',
            ]);
            $data['type'] = 'ssh';
            $data['ssh_port'] = $data['ssh_port'] ?? 22;
            $data['use_cloudflare'] = (bool) ($data['use_cloudflare'] ?? false);
        } else {
            $data = $request->validate([
                'name' => 'required|string|max:80',
                'base_url' => 'required|url|max:255',
                'token' => 'required|string|max:255',
                'portainer_url' => 'nullable|url|max:255',
            ]);
            $data['type'] = 'remote';
        }

        $server = Server::create($data);

        return response()->json(['message' => 'Server ditambahkan.', 'server' => $this->present($server)], 201);
    }

    public function update(Request $request, Server $server): JsonResponse
    {
        if ($server->isLocal()) {
            $data = $request->validate([
                'name' => 'required|string|max:80',
                'portainer_url' => 'nullable|url|max:255',
            ]);
            $server->update($data);

            return response()->json(['message' => 'Server diperbarui.', 'server' => $this->present($server->fresh())]);
        }

        $type = $request->input('type', $server->type);
        if (! in_array($type, ['ssh', 'remote'], true)) {
            $type = $server->type;
        }

        if ($type === 'ssh') {
            $data = $request->validate([
                'name' => 'required|string|max:80',
                'ssh_host' => 'required|string|max:255',
                'ssh_port' => 'nullable|integer|between:1,65535',
                'ssh_user' => 'required|string|max:100',
                'ssh_password' => 'nullable|string|max:500',
                'ssh_key' => 'nullable|string|max:16000',
                'use_cloudflare' => 'nullable|boolean',
                'portainer_url' => 'nullable|url|max:255',
            ]);
            $data['type'] = 'ssh';
            $data['ssh_port'] = $data['ssh_port'] ?? 22;
            $data['use_cloudflare'] = (bool) ($data['use_cloudflare'] ?? false);

            if (empty($data['ssh_password'])) {
                unset($data['ssh_password']);
            }
            if (empty($data['ssh_key'])) {
                unset($data['ssh_key']);
            }
        } else {
            $data = $request->validate([
                'name' => 'required|string|max:80',
                'base_url' => 'required|url|max:255',
                'token' => 'nullable|string|max:255',
                'portainer_url' => 'nullable|url|max:255',
            ]);
            $data['type'] = 'remote';
            if (empty($data['token'])) {
                unset($data['token']);
            }
        }

        $server->update($data);

        return response()->json(['message' => 'Server diperbarui.', 'server' => $this->present($server->fresh())]);
    }

    public function destroy(Request $request, Server $server): JsonResponse
    {
        if ($server->isLocal()) {
            return response()->json(['message' => 'Server lokal tidak dapat dihapus.'], 422);
        }

        if ((int) $request->session()->get('server_id') === $server->id) {
            $request->session()->forget('server_id');
        }
        $server->delete();

        return response()->json(['message' => 'Server dihapus.']);
    }

    public function test(Server $server): JsonResponse
    {
        if ($server->isLocal()) {
            return response()->json(['ok' => true, 'message' => 'Server lokal aktif.']);
        }

        $start = microtime(true);
        try {
            if ($server->isSsh()) {
                $this->ssh->metrics($server);
            } else {
                $this->agent->metrics($server);
            }
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Terhubung',
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Server $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'type' => $s->type,
            'base_url' => $s->base_url,
            'ssh_host' => $s->ssh_host,
            'ssh_port' => $s->ssh_port ?? 22,
            'ssh_user' => $s->ssh_user,
            'use_cloudflare' => (bool) $s->use_cloudflare,
            'has_password' => (bool) $s->ssh_password,
            'has_key' => (bool) $s->ssh_key,
            'portainer_url' => $s->portainer_url,
            'has_token' => (bool) $s->token,
        ];
    }

    /**
     * @param  array<string, mixed>  $res
     * @return array<string, mixed>
     */
    protected function summary(Server $s, array $res): array
    {
        $base = $this->present($s);

        if (! $res['ok']) {
            return $base + ['online' => false, 'error' => $res['error'] ?? 'Offline'];
        }

        $m = $res['data'];

        return $base + [
            'online' => true,
            'latency_ms' => $res['latency_ms'] ?? 0,
            'hostname' => $m['system']['hostname'] ?? null,
            'uptime' => $m['system']['uptime_formatted'] ?? null,
            'cpu' => $m['cpu']['usage_percent'] ?? 0,
            'ram' => $m['ram']['usage_percent'] ?? 0,
            'disk' => $m['disk']['usage_percent'] ?? 0,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\AgentClient;
use App\Services\StbMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerApiController extends Controller
{
    public function __construct(
        protected AgentClient $agent,
        protected StbMonitorService $stb,
    ) {}

    public function index(): JsonResponse
    {
        Server::local();

        return response()->json(Server::orderByRaw("type = 'local' desc")->orderBy('id')->get()->map(fn (Server $s) => $this->present($s)));
    }

    /**
     * Ringkasan semua server (status + CPU/RAM/Disk) untuk dashboard.
     */
    public function overview(): JsonResponse
    {
        $local = Server::local();
        $remotes = Server::where('type', 'remote')->where('is_active', true)->orderBy('id')->get();
        $remote = $this->agent->metricsMany($remotes);

        $rows = [$this->summary($local, ['ok' => true, 'data' => $this->stb->getSystemMetrics(), 'latency_ms' => 0])];

        foreach ($remotes as $server) {
            $res = $remote[$server->id];
            if ($res['ok']) {
                $this->stb->recordSnapshot($res['data'], $server->id);
            }
            $rows[] = $this->summary($server, $res);
        }

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'base_url' => 'required|url|max:255',
            'token' => 'required|string|max:255',
            'portainer_url' => 'nullable|url|max:255',
        ]);

        $server = Server::create($data + ['type' => 'remote']);

        return response()->json(['message' => 'Server ditambahkan.', 'server' => $this->present($server)], 201);
    }

    public function update(Request $request, Server $server): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:80',
            'portainer_url' => 'nullable|url|max:255',
        ];
        if (! $server->isLocal()) {
            $rules += ['base_url' => 'required|url|max:255', 'token' => 'nullable|string|max:255'];
        }

        $data = $request->validate($rules);
        if (empty($data['token'])) {
            unset($data['token']); // kosong = pertahankan token lama
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
            $this->agent->metrics($server);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(['ok' => true, 'message' => 'Terhubung', 'latency_ms' => (int) round((microtime(true) - $start) * 1000)]);
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

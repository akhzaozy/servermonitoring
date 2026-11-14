<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\AgentClient;
use App\Services\TerminalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalApiController extends Controller
{
    public function exec(Request $request, TerminalService $terminal, AgentClient $agent): JsonResponse
    {
        if (! config('monitor.terminal.enabled')) {
            return response()->json(['message' => 'Terminal dinonaktifkan (TERMINAL_ENABLED=false).'], 403);
        }

        $data = $request->validate([
            'server' => 'nullable|integer',
            'command' => 'present|nullable|string|max:4000',
            'cwd' => 'nullable|string|max:1000',
        ]);

        $server = Server::resolve($data['server'] ?? null);
        $command = (string) ($data['command'] ?? '');

        if ($server->isLocal()) {
            return response()->json($terminal->run($command, $data['cwd'] ?? null));
        }

        try {
            return response()->json($agent->exec($server, $command, $data['cwd'] ?? null));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StbMonitorService;
use App\Services\TerminalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint yang dipanggil dashboard utama untuk memonitor server ini.
 */
class AgentController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json(['ok' => true, 'hostname' => config('monitor.hostname') ?: gethostname()]);
    }

    public function metrics(StbMonitorService $stb): JsonResponse
    {
        return response()->json($stb->getSystemMetrics());
    }

    public function exec(Request $request, TerminalService $terminal): JsonResponse
    {
        if (! config('monitor.agent.allow_exec') || ! config('monitor.terminal.enabled')) {
            return response()->json(['message' => 'Terminal remote dinonaktifkan di agent ini.'], 403);
        }

        $data = $request->validate([
            'command' => 'present|nullable|string|max:4000',
            'cwd' => 'nullable|string|max:1000',
        ]);

        return response()->json($terminal->run((string) ($data['command'] ?? ''), $data['cwd'] ?? null));
    }
}

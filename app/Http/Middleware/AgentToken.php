<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('monitor.agent.token');

        if ($expected === '') {
            return response()->json(['message' => 'Agent belum dikonfigurasi (AGENT_TOKEN kosong).'], 503);
        }

        $given = (string) $request->bearerToken();

        if (! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        }

        return $next($request);
    }
}

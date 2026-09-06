<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupApiController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Get backup status, latest run summary, and file details.
     */
    public function getStatus(): JsonResponse
    {
        $status = $this->backupService->getBackupStatus();

        return response()->json($status);
    }

    /**
     * Trigger manual backup execution.
     */
    public function runBackup(Request $request): JsonResponse
    {
        $result = $this->backupService->runManualBackup();

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Get latest backup log lines for streaming/terminal view.
     */
    public function getLogs(Request $request): JsonResponse
    {
        $lines = (int) $request->input('lines', 50);
        $logs = $this->backupService->getRecentLogs($lines);
        $isRunning = $this->backupService->isRunning();

        return response()->json([
            'is_running' => $isRunning,
            'logs' => $logs,
        ]);
    }
}

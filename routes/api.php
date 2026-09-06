<?php

use App\Http\Controllers\Api\BackupApiController;
use App\Http\Controllers\Api\SiteApiController;
use App\Http\Controllers\Api\SystemApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('monitoring')->group(function () {
    Route::get('/metrics', [SystemApiController::class, 'getMetrics']);
    Route::get('/nginx', [SystemApiController::class, 'getNginxInfo']);
    Route::post('/nginx/test', [SystemApiController::class, 'testNginx']);
    Route::post('/nginx/reload', [SystemApiController::class, 'reloadNginx']);
    Route::post('/nginx/sync', [SystemApiController::class, 'syncNginx']);

    Route::get('/sites', [SiteApiController::class, 'index']);
    Route::post('/sites', [SiteApiController::class, 'store']);
    Route::put('/sites/{site}', [SiteApiController::class, 'update']);
    Route::delete('/sites/{site}', [SiteApiController::class, 'destroy']);
    Route::post('/sites/{site}/check', [SiteApiController::class, 'check']);
    Route::post('/check-all', [SiteApiController::class, 'checkAll']);

    // STB Server Backup Routes (backup.php)
    Route::get('/backup/status', [BackupApiController::class, 'getStatus']);
    Route::post('/backup/run', [BackupApiController::class, 'runBackup']);
    Route::get('/backup/logs', [BackupApiController::class, 'getLogs']);
});

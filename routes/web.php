<?php

use App\Http\Controllers\Api\BackupApiController;
use App\Http\Controllers\Api\ServerApiController;
use App\Http\Controllers\Api\SiteApiController;
use App\Http\Controllers\Api\SystemApiController;
use App\Http\Controllers\Api\TerminalApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Pages
    Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/websites', [PageController::class, 'websites'])->name('websites');
    Route::get('/backups', [PageController::class, 'backups'])->name('backups');
    Route::get('/servers', [PageController::class, 'servers'])->name('servers');
    Route::get('/terminal', [PageController::class, 'terminal'])->name('terminal');
    Route::get('/servers/{server}/select', [PageController::class, 'selectServer'])->name('servers.select');

    // JSON API (session-protected)
    Route::prefix('api/monitoring')->group(function () {
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

        Route::get('/backup/status', [BackupApiController::class, 'getStatus']);
        Route::post('/backup/run', [BackupApiController::class, 'runBackup']);
        Route::get('/backup/logs', [BackupApiController::class, 'getLogs']);

        Route::get('/servers', [ServerApiController::class, 'index']);
        Route::get('/servers/overview', [ServerApiController::class, 'overview']);
        Route::post('/servers', [ServerApiController::class, 'store']);
        Route::put('/servers/{server}', [ServerApiController::class, 'update']);
        Route::delete('/servers/{server}', [ServerApiController::class, 'destroy']);
        Route::post('/servers/{server}/test', [ServerApiController::class, 'test']);

        Route::post('/terminal/exec', [TerminalApiController::class, 'exec'])->middleware('throttle:120,1');
    });
});

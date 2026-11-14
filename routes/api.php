<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

// Endpoint agent: dipanggil dashboard utama dengan Bearer token (AGENT_TOKEN).
Route::prefix('agent')->middleware(['agent.token', 'throttle:120,1'])->group(function () {
    Route::get('/ping', [AgentController::class, 'ping']);
    Route::get('/metrics', [AgentController::class, 'metrics']);
    Route::post('/exec', [AgentController::class, 'exec']);
});

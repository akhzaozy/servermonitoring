<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Main Dashboard View
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

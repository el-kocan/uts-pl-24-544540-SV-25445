<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes are prefixed with /api automatically by Laravel.
|
*/

// Health check endpoint (public, no middleware)
Route::get('/health', [HealthController::class, 'check']);

// Public auth routes (no middleware)
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Equipment CRUD
    Route::apiResource('equipments', EquipmentController::class);
});

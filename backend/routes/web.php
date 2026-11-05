<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint Health Check sistem (memeriksa database dan cache)
Route::get('/health', [HealthController::class, 'check']);


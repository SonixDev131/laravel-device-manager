<?php

declare(strict_types=1);

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::post('/agents/register', [AgentController::class, 'register']);
// ->middleware('auth:sanctum');

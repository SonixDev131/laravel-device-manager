<?php

declare(strict_types=1);

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentUpdateController;
use App\Http\Controllers\AgentUploadController;
use App\Http\Controllers\GetLatestVersionController;
use Illuminate\Support\Facades\Route;

Route::post('/agents/register', [AgentController::class, 'register']);
// ->middleware('auth:sanctum');
Route::post('/agent/upload', AgentUploadController::class)->name('upload');
/* ->middleware('auth:sanctum'); */
Route::post('/agent/latest', GetLatestVersionController::class)->name('latest');
Route::post('/agent/update', AgentUpdateController::class)->name('update');

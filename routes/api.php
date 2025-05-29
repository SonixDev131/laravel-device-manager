<?php

declare(strict_types=1);

use App\Http\Controllers\AgentCommandResultController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentInstallerController;
use App\Http\Controllers\AgentUpdateController;
use App\Http\Controllers\GetLatestVersionController;
use Illuminate\Support\Facades\Route;

Route::post('/agents/register', [AgentController::class, 'register']);
Route::get('/agent/version', GetLatestVersionController::class)->name('version');
Route::get('/agent/update/{version}', AgentUpdateController::class)->name('update');
Route::get('/agent/installer/{installerId}', AgentInstallerController::class)->name('installer');
Route::post('/agent/command-result', AgentCommandResultController::class)->name('command-result');

<?php

declare(strict_types=1);

use App\Http\Controllers\AgentsController;
use App\Http\Controllers\RoomBlockedWebsitesController;
use App\Http\Controllers\RoomCommandController;
use App\Http\Controllers\RoomCommandHistoryController;
use App\Http\Controllers\RoomComputerController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Rooms
    Route::resource('rooms', RoomController::class)
        ->only(['index', 'store', 'update', 'destroy', 'show']);

    Route::post('/rooms/{room}/computers', [RoomComputerController::class, 'store'])
        ->name('rooms.computers.store');

    Route::post('/rooms/{room}/commands', [RoomCommandController::class, 'publish'])->name('rooms.commands.publish');

    Route::get('/rooms/{room}/commands', [RoomCommandHistoryController::class, 'index'])->name('rooms.commands.index');

    Route::get('/rooms/{room}/blocked-websites', [RoomBlockedWebsitesController::class, 'index'])->name('rooms.blocked-websites.index');

    Route::post('/rooms/{room}/update-agents', [RoomController::class, 'updateAgents'])
        ->name('rooms.update-agents');

    Route::post('rooms/import', [RoomController::class, 'import'])->name('rooms.import');

    // System-wide agent management (admin only)
    // Route::middleware(['can:update-agents'])->group(function () {
    Route::get('/agents', [AgentsController::class, 'index'])->name('agents.index');
    Route::post('/agents/update-all', [AgentsController::class, 'updateAll'])->name('agents.update-all');
    Route::post('/agents/upload-package', [AgentsController::class, 'uploadPackage'])->name('agents.upload-package');
    Route::delete('/agents/packages/{package}', [AgentsController::class, 'deletePackage'])->name('agents.packages.delete');
    Route::post('/agents/upload-installer', [AgentsController::class, 'uploadInstaller'])->name('agents.upload-installer');
    Route::post('/agents/installers/{installer}/broadcast', [AgentsController::class, 'broadcastInstaller'])->name('agents.installers.broadcast');
    Route::delete('/agents/installers/{installer}', [AgentsController::class, 'deleteInstaller'])->name('agents.installers.delete');

    // });
});

// Profile routes
// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

// Agent download endpoints (to be implemented later)
// Route::get('/api/download/agent/{os}', [AgentDownloadController::class, 'download'])
//     ->name('api.download.agent')
//     ->where('os', 'windows|linux|mac');

// Agent registration endpoint (to be implemented later)
// Route::post('/api/computers/register', [\ComputerController::class, 'register'])
//     ->name('api.computers.register');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

<?php

declare(strict_types=1);

use App\Http\Controllers\RoomCommandController;
use App\Http\Controllers\RoomComputerController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('rooms', RoomController::class)
        ->only(['index', 'store', 'update', 'destroy', 'show']);

    Route::post('/rooms/{room}/computers', [RoomComputerController::class, 'store'])
        ->name('rooms.computers.store');

    Route::post('/rooms/{room}/commands', [RoomCommandController::class, 'handleCommand'])
        ->name('rooms.commands.dispatch');

    Route::post('rooms/import', [RoomController::class, 'import'])->name('rooms.import');
});

// Agent download endpoints (to be implemented later)
// Route::get('/api/download/agent/{os}', [AgentDownloadController::class, 'download'])
//     ->name('api.download.agent')
//     ->where('os', 'windows|linux|mac');

// Agent registration endpoint (to be implemented later)
// Route::post('/api/computers/register', [\ComputerController::class, 'register'])
//     ->name('api.computers.register');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

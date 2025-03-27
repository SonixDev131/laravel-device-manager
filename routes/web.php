<?php

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
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('rooms/{room}/layout', [RoomController::class, 'showLayout'])->name('rooms.layout');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

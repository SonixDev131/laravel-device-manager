<?php

declare(strict_types=1);

use App\Http\Controllers\AgentsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\RoomBlockedWebsitesController;
use App\Http\Controllers\RoomCommandController;
use App\Http\Controllers\RoomCommandHistoryController;
use App\Http\Controllers\RoomComputerController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home')->middleware('guest');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    // Route::get('/dashboard', function () {
    //     return Inertia::render('Dashboard');
    // })->name('dashboard');

    // Room Management
    Route::middleware(['can:manage-rooms'])->group(function () {
        Route::get('/admin/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/admin/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::patch('/admin/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    });

    // Room Assignment Management (Admin only)
    Route::middleware(['can:manage-user-rooms'])->group(function () {
        Route::get('/admin/room-assignments', [RoomController::class, 'adminRoomAssignments'])->name('admin.room-assignments');
        Route::get('/admin/room-import', [RoomController::class, 'adminImport'])->name('admin.room-import');
        Route::post('/admin/room-import', [RoomController::class, 'import'])->name('rooms.import');
        Route::post('/admin/assign-teacher', [RoomController::class, 'assignTeacher'])->name('admin.assign-teacher');
        Route::delete('/admin/room-assignments/{assignment}', [RoomController::class, 'removeAssignment'])->name('admin.remove-assignment');
        Route::patch('/admin/room-assignments/{assignment}', [RoomController::class, 'updateAssignment'])->name('admin.update-assignment');
    });

    // Teacher Management (Admin only)
    Route::middleware(['can:manage-users'])->group(function () {
        Route::get('/admin/teachers', [TeacherController::class, 'index'])->name('admin.teachers.index');
        Route::post('/admin/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
        Route::delete('/admin/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');
    });

    // Teacher Room Management
    Route::get('/teacher/my-rooms', [RoomController::class, 'teacherRooms'])->name('teacher.rooms');

    // Room-specific operations (requires room access for teachers, always allowed for admins)
    Route::middleware(['room.access'])->group(function () {
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/rooms/{room}/commands', [RoomCommandController::class, 'publish'])->name('rooms.commands.publish');
        Route::get('/rooms/{room}/commands', [RoomCommandHistoryController::class, 'index'])->name('rooms.commands.index');
        Route::get('/rooms/{room}/blocked-websites', [RoomBlockedWebsitesController::class, 'index'])->name('rooms.blocked-websites.index');
        Route::post('/rooms/{room}/update-agents', [RoomController::class, 'updateAgents'])->name('rooms.update-agents');
        Route::post('/rooms/{room}/computers', [RoomComputerController::class, 'store'])->name('rooms.computers.store');

        // Additional room operations
        Route::post('/rooms/{room}/send-command', [RoomController::class, 'sendCommand'])->name('rooms.send-command');
        Route::post('/rooms/{room}/screenshot', [RoomController::class, 'takeScreenshot'])->name('rooms.screenshot');
        Route::post('/rooms/{room}/block-websites', [RoomController::class, 'blockWebsites'])->name('rooms.block-websites');
    });

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

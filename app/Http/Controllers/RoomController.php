<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssignTeacherToRoomAction;
use App\Actions\CreateRoomAction;
use App\Actions\DeleteRoomAction;
use App\Actions\UpdateRoomAction;
use App\Enums\PermissionsEnum;
use App\Http\Requests\DeleteRoomRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Computer;
use App\Models\Room;
use App\Models\User;
use App\Models\UserRoomAssignment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): InertiaResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $rooms = $user->hasRole('super-admin') ? Room::all() : $user->activeAssignedRooms()->get();

        return Inertia::render('rooms/RoomIndex', [
            'rooms' => $rooms,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request, CreateRoomAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('rooms.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room, UpdateRoomAction $action): RedirectResponse
    {
        $action->handle($room, $request->validated());

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        DeleteRoomRequest $request,
        Room $room,
        DeleteRoomAction $action
    ): RedirectResponse {
        $action->handle($room);

        return to_route('rooms.index');
    }

    /**
     * Display the layout of the specified room.
     */
    public function show(Room $room): InertiaResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Load room with computers
        $room->load('computers.latestMetric');

        return Inertia::render('rooms/RoomLayout', [
            'room' => $room,
            'userAccess' => [
                'can_send_commands' => $user->can(PermissionsEnum::SEND_LOCK_COMMAND->value),
                'can_take_screenshots' => $user->can(PermissionsEnum::TAKE_SCREENSHOT->value),
                'can_block_websites' => $user->can(PermissionsEnum::BLOCK_WEBSITES->value),
                'can_manage_computers' => $user->can(PermissionsEnum::MANAGE_COMPUTERS->value),
            ],
        ]);
    }

    /**
     * Show the admin room import page.
     */
    public function adminImport(): InertiaResponse
    {
        return Inertia::render('admin/RoomImport');
    }

    /**
     * Import rooms and computers from JSON data or file upload.
     *
     * @throws ValidationException
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'jsonFile' => 'required|file|mimes:json',
        ]);

        try {
            $file = $request->file('jsonFile');
            $contents = $file->get();
            $data = json_decode($contents, true);

            foreach ($data['rooms'] as $roomData) {
                $room = Room::updateOrCreate(
                    ['name' => $roomData['name']],
                    ['grid_rows' => $roomData['grid_rows'], 'grid_cols' => $roomData['grid_cols']],
                );

                foreach ($roomData['computers'] as $computerData) {
                    Computer::updateOrCreate(
                        ['mac_address' => $computerData['mac_address']],
                        [
                            'room_id' => $room->id,
                            'hostname' => $computerData['hostname'] ?? '',
                            'pos_row' => $computerData['pos_row'],
                            'pos_col' => $computerData['pos_col'],
                        ]
                    );
                }
            }

            return redirect()->back()->with('success', 'Import thành công');
        } catch (Exception $e) {
            Log::error("Lỗi import file: {$e->getMessage()}");

            return redirect()->back()->with('error', 'Lỗi import file');
        }
    }

    /**
     * Send command to room computers - requires room access
     */
    public function sendCommand(Request $request, Room $room): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $command = $request->input('command');

        // Check permission based on command type
        $requiredPermission = match ($command) {
            'lock' => PermissionsEnum::SEND_LOCK_COMMAND,
            'restart' => PermissionsEnum::SEND_RESTART_COMMAND,
            'message' => PermissionsEnum::SEND_MESSAGE_COMMAND,
            default => null,
        };

        if (! $requiredPermission || ! $user->can($requiredPermission->value)) {
            return response()->json(['message' => 'Unauthorized for this command'], Response::HTTP_FORBIDDEN);
        }

        // Here you would implement the actual command sending logic
        // For now, just return success

        return response()->json([
            'message' => "Command '{$command}' sent to {$room->name}",
            'room_id' => $room->id,
            'command' => $command,
        ]);
    }

    /**
     * Take screenshot in room - requires room access
     */
    public function takeScreenshot(Room $room): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::TAKE_SCREENSHOT->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Screenshot logic would go here
        return response()->json([
            'message' => "Screenshot taken in {$room->name}",
            'room_id' => $room->id,
        ]);
    }

    /**
     * Block websites in room - requires room access
     */
    public function blockWebsites(Request $request, Room $room): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::BLOCK_WEBSITES->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $websites = $request->input('websites', []);

        // Website blocking logic would go here
        return response()->json([
            'message' => "Websites blocked in {$room->name}",
            'room_id' => $room->id,
            'blocked_websites' => $websites,
        ]);
    }

    /**
     * Update agents in room - requires room access
     */
    public function updateAgents(Room $room): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::UPDATE_ROOM_AGENTS->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Agent update logic would go here
        return response()->json([
            'message' => "Agents updated in {$room->name}",
            'room_id' => $room->id,
        ]);
    }

    /**
     * Assign teacher to room - admin only
     */
    public function assignTeacher(Request $request, AssignTeacherToRoomAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::MANAGE_USER_ROOMS->value)) {
            return redirect()->back(Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $teacher = User::findOrFail($request->input('teacher_id'));
        $room = Room::findOrFail($request->input('room_id'));
        $expiresAt = $request->input('expires_at') ? now()->parse($request->input('expires_at')) : null;

        $action->handle($teacher, $room, $expiresAt);

        return redirect()->back();
    }

    /**
     * Admin page to manage room assignments
     */
    public function adminRoomAssignments(): InertiaResponse
    {
        $assignments = UserRoomAssignment::with(['user', 'room'])
            ->orderBy('created_at', 'desc')
            ->get();

        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->get();

        $rooms = Room::all();

        return Inertia::render('admin/RoomAssignments', [
            'assignments' => $assignments,
            'teachers' => $teachers,
            'rooms' => $rooms,
        ]);
    }

    /**
     * Remove room assignment
     */
    public function removeAssignment(UserRoomAssignment $assignment): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::MANAGE_USER_ROOMS->value)) {
            return redirect()->back(Response::HTTP_FORBIDDEN);
        }

        $assignment->delete();

        return redirect()->back();
    }

    /**
     * Update room assignment
     */
    public function updateAssignment(Request $request, UserRoomAssignment $assignment): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::MANAGE_USER_ROOMS->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $assignment->update($request->only(['is_active', 'expires_at']));

        return redirect()->back();
    }

    /**
     * Teacher's room management page
     */
    public function teacherRooms(): InertiaResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $rooms = $user->activeAssignedRooms()->with('computers.latestMetric')->get();

        return Inertia::render('teacher/MyRooms', [
            'rooms' => $rooms,
            'userPermissions' => [
                'can_send_commands' => $user->can(PermissionsEnum::SEND_LOCK_COMMAND->value),
                'can_take_screenshots' => $user->can(PermissionsEnum::TAKE_SCREENSHOT->value),
                'can_block_websites' => $user->can(PermissionsEnum::BLOCK_WEBSITES->value),
                'can_view_command_history' => $user->can(PermissionsEnum::VIEW_COMMAND_HISTORY->value),
            ],
        ]);
    }
}

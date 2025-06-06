<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssignTeacherToRoomAction;
use App\Enums\PermissionsEnum;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Example controller demonstrating room-specific access control
 * This shows how to implement the room-based permissions system
 */
class ExampleRoomAccessController extends Controller
{
    public function __construct()
    {
        // Apply room access middleware to specific methods
        $this->middleware('auth');
        $this->middleware('room.access')->only([
            'show',
            'sendCommand',
            'takeScreenshot',
            'blockWebsites',
        ]);
    }

    /**
     * Display a specific room - requires room access
     */
    public function show(Room $room): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Check specific permissions
        if (! $user->can(PermissionsEnum::VIEW_ROOMS->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // Load room with computers
        $room->load('computers');

        return response()->json([
            'room' => $room,
            'user_access' => [
                'can_send_commands' => $user->can(PermissionsEnum::SEND_LOCK_COMMAND->value),
                'can_take_screenshots' => $user->can(PermissionsEnum::TAKE_SCREENSHOT->value),
                'can_block_websites' => $user->can(PermissionsEnum::BLOCK_WEBSITES->value),
            ],
        ]);
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
     * List all rooms - no room-specific access needed (global permission check)
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::VIEW_ROOMS->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // For teachers, only show their assigned rooms
        // For admins, show all rooms
        if ($user->hasRole('super-admin')) {
            $rooms = Room::all();
        } else {
            $rooms = $user->activeAssignedRooms;
        }

        return response()->json(['rooms' => $rooms]);
    }

    /**
     * Assign teacher to room - admin only
     */
    public function assignTeacher(Request $request, AssignTeacherToRoomAction $action): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->can(PermissionsEnum::MANAGE_USER_ROOMS->value)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $teacher = User::findOrFail($request->input('teacher_id'));
        $room = Room::findOrFail($request->input('room_id'));
        $expiresAt = $request->input('expires_at') ? now()->parse($request->input('expires_at')) : null;

        $assignment = $action->handle($teacher, $room, $expiresAt);

        return response()->json([
            'message' => 'Teacher assigned to room successfully',
            'assignment' => $assignment,
        ]);
    }
}

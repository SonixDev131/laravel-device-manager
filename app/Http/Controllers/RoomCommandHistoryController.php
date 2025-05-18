<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GetRoomCommandsRequest;
use App\Models\Command;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

final class RoomCommandHistoryController extends Controller
{
    /**
     * Get the command history for a room
     */
    public function index(GetRoomCommandsRequest $request, Room $room): JsonResponse
    {
        $commands = Command::query()
            ->whereHas('computer', fn ($query) => $query->where('room_id', $room->id))
            ->with('computer')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($command) => [
                'id' => $command->id,
                'type' => $command->type,
                'status' => $command->status,
                'created_at' => $command->created_at,
                'completed_at' => $command->completed_at,
                'target' => $command->computer ? $command->computer->hostname : 'Room',
                'is_group_command' => $command->is_group_command,
            ]);

        return response()->json(['data' => $commands]);
    }
}

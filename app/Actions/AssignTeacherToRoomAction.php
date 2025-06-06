<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RolesEnum;
use App\Models\Room;
use App\Models\User;
use App\Models\UserRoomAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignTeacherToRoomAction
{
    /**
     * Assign a teacher to a room with optional expiration.
     */
    public function handle(
        User $teacher,
        Room $room,
        ?Carbon $expiresAt = null,
        bool $isActive = true
    ): UserRoomAssignment {
        // Validate that the user has teacher role
        if (! $teacher->hasRole(RolesEnum::TEACHER->value)) {
            throw new InvalidArgumentException('User must have teacher role to be assigned to a room');
        }

        return DB::transaction(function () use ($teacher, $room, $expiresAt, $isActive) {
            // Check if assignment already exists
            $existingAssignment = UserRoomAssignment::where('user_id', $teacher->id)
                ->where('room_id', $room->id)
                ->first();

            if ($existingAssignment) {
                // Update existing assignment
                $existingAssignment->update([
                    'is_active' => $isActive,
                    'expires_at' => $expiresAt,
                    'assigned_at' => now(),
                ]);

                return $existingAssignment;
            }

            // Create new assignment
            return UserRoomAssignment::create([
                'user_id' => $teacher->id,
                'room_id' => $room->id,
                'is_active' => $isActive,
                'assigned_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Remove teacher from room.
     */
    public function revoke(User $teacher, Room $room): bool
    {
        return UserRoomAssignment::where('user_id', $teacher->id)
            ->where('room_id', $room->id)
            ->delete() > 0;
    }

    /**
     * Deactivate assignment instead of deleting.
     */
    public function deactivate(User $teacher, Room $room): bool
    {
        return UserRoomAssignment::where('user_id', $teacher->id)
            ->where('room_id', $room->id)
            ->update(['is_active' => false]) > 0;
    }
}

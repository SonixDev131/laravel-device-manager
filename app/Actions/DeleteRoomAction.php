<?php

namespace App\Actions;

use App\Models\Room;

final class DeleteRoomAction
{
    public function handle(Room $room): void
    {
        $room->delete();
    }
}

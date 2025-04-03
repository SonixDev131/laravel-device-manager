<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Room;

final class DeleteRoomAction
{
    public function handle(Room $room): void
    {
        $room->delete();
    }
}

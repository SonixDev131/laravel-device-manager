<?php

namespace App\Actions;

use App\Models\Room;

class CreateComputerAction
{
    public function handle(Room $room, array $data): void
    {

        $room->computers()->create($data);

    }
}

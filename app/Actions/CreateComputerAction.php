<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Room;

final class CreateComputerAction
{
    public function handle(Room $room, array $data): void
    {

        $room->computers()->create($data);

    }
}

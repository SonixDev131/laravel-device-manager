<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Computer;

final class UpdateComputerAction
{
    public function handle(Computer $computer, array $data): Computer
    {
        $computer->update($data);

        return $computer->fresh();
    }
}

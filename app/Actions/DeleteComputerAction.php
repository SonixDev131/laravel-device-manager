<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Computer;

final class DeleteComputerAction
{
    public function handle(Computer $computer): void
    {
        // Delete related commands first to avoid foreign key constraints
        $computer->commands()->delete();

        // Delete related metrics
        $computer->metrics()->delete();

        // Finally delete the computer
        $computer->delete();
    }
}

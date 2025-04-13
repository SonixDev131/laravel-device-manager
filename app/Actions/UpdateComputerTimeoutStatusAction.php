<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComputerStatus;
use App\Models\Computer;
use Illuminate\Support\Facades\Log;

final class UpdateComputerTimeoutStatusAction
{
    /**
     * Update status of computers based on their last_seen_at timestamp
     *
     * @param  int  $timeoutInMinutes  The number of minutes to consider a computer offline after last heartbeat
     * @return array<string, int> Statistics about the update operation
     */
    public function handle(int $timeoutInMinutes = 3): array
    {
        $stats = [
            'total' => 0,
            'changed' => 0,
            'offline' => 0,
            'online' => 0,
            'idle' => 0,
            'shutting_down' => 0,
            'maintenance' => 0,
        ];

        // Get all computers except those in maintenance mode
        $computers = Computer::query()
            ->where('status', '!=', ComputerStatus::MAINTENANCE->value)
            ->get();

        $stats['total'] = $computers->count();

        foreach ($computers as $computer) {
            $wasChanged = $computer->updateStatusBasedOnTimeout($timeoutInMinutes);

            if ($wasChanged) {
                $stats['changed']++;
            }

            // Count computers by status
            match ($computer->status) {
                ComputerStatus::OFFLINE => $stats['offline']++,
                ComputerStatus::ONLINE => $stats['online']++,
                ComputerStatus::IDLE => $stats['idle']++,
                ComputerStatus::SHUTTING_DOWN => $stats['shutting_down']++,
                ComputerStatus::MAINTENANCE => $stats['maintenance']++,
                default => null,
            };
        }

        // Also count computers in maintenance mode
        $maintenanceCount = Computer::query()
            ->where('status', ComputerStatus::MAINTENANCE->value)
            ->count();

        $stats['maintenance'] = $maintenanceCount;
        $stats['total'] += $maintenanceCount;

        // Log the results
        Log::info('Computer timeout status update completed', $stats);

        return $stats;
    }
}

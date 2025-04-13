<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Computer;
use App\Models\Room;
use Illuminate\Support\Facades\Log;

final class ProcessComputerHeartbeatAction
{
    /**
     * Process a heartbeat message from a computer agent
     *
     * @param  string  $computerId  The UUID of the computer
     * @param  string  $roomId  The UUID of the room
     * @param  string  $status  The status reported by the agent
     * @param  array<string, mixed>  $metrics  System metrics reported by the agent
     * @return array<string, mixed> Result of the operation
     */
    public function handle(string $computerId, string $roomId, string $status, array $metrics = []): array
    {
        // Find the computer by ID
        $computer = Computer::query()->find($computerId);

        // If computer doesn't exist, we might want to auto-register it
        if (! $computer) {
            // Verify that the room exists
            $room = Room::query()->find($roomId);

            if (! $room) {
                Log::warning('Heartbeat received for unknown room', [
                    'computer_id' => $computerId,
                    'room_id' => $roomId,
                ]);

                return [
                    'success' => false,
                    'message' => 'Room not found',
                ];
            }

            // Auto-register the computer
            $computer = new Computer;
            $computer->id = $computerId;
            $computer->room_id = $roomId;
            $computer->name = $metrics['hostname'] ?? 'New Computer';
            $computer->mac_address = $metrics['mac_address'] ?? '';
            $computer->ip_address = $metrics['ip_address'] ?? '';
            // Set default position (can be updated later)
            $computer->pos_row = 1;
            $computer->pos_col = 1;
            $computer->save();

            Log::info('New computer auto-registered from heartbeat', [
                'computer_id' => $computerId,
                'room_id' => $roomId,
            ]);
        }

        // Update the computer status
        $statusChanged = $computer->updateFromHeartbeat($status, $metrics);

        if ($statusChanged) {
            Log::info('Computer status updated from heartbeat', [
                'computer_id' => $computerId,
                'status' => $status,
                'previous_status' => $computer->getOriginal('status'),
            ]);
        }

        return [
            'success' => true,
            'status_changed' => $statusChanged,
            'computer_id' => $computerId,
        ];
    }
}

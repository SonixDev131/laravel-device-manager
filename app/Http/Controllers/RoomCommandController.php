<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PublishComputerCommandAction;
use App\Http\Requests\PublishComputerCommandRequest;
use App\Models\Room;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class RoomCommandController extends Controller
{
    public function handleCommand(
        PublishComputerCommandRequest $request,
        Room $room,
        PublishComputerCommandAction $action
    ): RedirectResponse {
        $validated = $request->validated();
        $commandType = $validated['command_type'];
        $success = false;

        try {
            $success = match ($validated['target_type']) {
                'single' => $action->publishCommandToComputer(
                    computerId: $validated['computer_id'],
                    roomId: $room->id,
                    commandType: $commandType
                ),
                'group' => ! empty($validated['computer_ids'])
                    ? $this->handleGroupCommand($action, $validated['computer_ids'], $room->id, $commandType)
                    : false,
                'all' => $action->publishCommandToRoom(
                    roomId: $room->id,
                    commandType: $commandType
                ),
                default => false,
            };

            if ($success) {
                return to_route('rooms.show', $room->id)
                    ->with('status', 'Command sent successfully.');
            }

            return to_route('rooms.show', $room->id)
                ->with('error', 'Failed to send command. Please try again later.');
        } catch (Exception $e) {
            Log::error('Failed to publish command via RabbitMQ', [
                'room_id' => $room->id,
                'command_type' => $commandType,
                'error' => $e->getMessage(),
            ]);

            return to_route('rooms.show', $room->id)
                ->with('error', 'An error occurred while sending the command.');
        }
    }

    /**
     * Handle command publishing to a group of computers
     */
    private function handleGroupCommand(
        PublishComputerCommandAction $action,
        array $computerIds,
        string $roomId,
        string $commandType
    ): bool {
        $results = $action->publishCommandToMultipleComputers(
            computerIds: $computerIds,
            roomId: $roomId,
            commandType: $commandType
        );

        // Consider the operation successful if at least one message was sent successfully
        return ! empty(array_filter($results));
    }
}

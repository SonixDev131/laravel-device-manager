<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Room;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PublishCommandAction
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        private readonly RabbitMQService $rabbitMQService
    ) {}

    /**
     * Handle the command publishing based on target type
     *
     * @param  Room  $room  Target room
     * @param  array{
     *   command_type: \App\Enums\CommandType,
     *   target_type: 'single'|'group'|'all',
     *   computer_id: string,
     *   computer_ids: array<string>
     * }  $data  Command data
     * @return bool Whether the command was published successfully
     */
    public function handle(Room $room, array $data): bool
    {
        $targetType = $data['target_type'];
        $commandType = CommandType::from($data['command_type']);

        try {
            return match ($targetType) {
                'single' => $this->publishToSingleComputer($room->id, $data['computer_id'], $commandType),
                'group' => $this->publishToMultipleComputers($room->id, $data['computer_ids'], $commandType),
                'all' => $this->publishToAllComputers($room, $commandType),
            };
        } catch (Throwable $e) {
            Log::error('Command publishing failed', [
                'error' => $e->getMessage(),
                'room_id' => $room->id,
                'command_type' => $commandType,
            ]);

            return false;
        }
    }

    /**
     * Publish a system-wide broadcast command to all agents
     *
     * @param  CommandType  $commandType  Type of command
     * @param  array<string, mixed>  $params  Additional parameters
     * @return bool Success status
     */
    public function publishSystemBroadcast(CommandType $commandType, array $params = []): bool
    {
        // Create a system-level command record
        $command = Command::create([
            'type' => $commandType,
            'status' => CommandStatus::PENDING,
            'params' => $params,
            'is_broadcast' => true,
        ]);

        // Check if RabbitMQ is available
        if (! $this->rabbitMQService->isAvailable()) {
            Log::warning('RabbitMQ unavailable, broadcast command saved to database for later processing', [
                'command_id' => $command->id,
            ]);

            // Set status to queued for later processing
            $command->status = CommandStatus::QUEUED;
            $command->save();

            // Dispatch event for alternate processing if needed
            Event::dispatch('command.queued', $command);

            return true;
        }

        // Create message payload
        $payload = [
            'command_id' => $command->id,
            'type' => $commandType->value,
            'params' => $params,
            'timestamp' => time(),
            'is_broadcast' => true,
        ];

        // Publish to all agents using broadcast exchange
        $published = $this->rabbitMQService->publishBroadcast($payload);

        // Update command status based on publishing result
        if (! $published) {
            $command->status = CommandStatus::QUEUED;
            $command->save();

            // Dispatch event for alternate processing
            Event::dispatch('command.queued', $command);

            Log::info('Broadcast command queued for later delivery due to RabbitMQ issue', [
                'command_id' => $command->id,
            ]);
        } else {
            $command->status = CommandStatus::SENT;
            $command->save();

            Log::info('Broadcast command published successfully to all agents', [
                'command_id' => $command->id,
            ]);
        }

        return true;
    }

    /**
     * Publish command to a single computer
     *
     * @param  string  $roomId  Room identifier
     * @param  string  $computerId  Computer identifier
     * @param  CommandType  $commandType  Type of command
     * @return bool Success status
     */
    private function publishToSingleComputer(
        string $roomId,
        string $computerId,
        CommandType $commandType,
    ): bool {
        // Verify computer belongs to room
        $computer = Computer::query()
            ->where('id', $computerId)
            ->where('room_id', $roomId)
            ->first();

        if (! $computer) {
            Log::warning('Attempted to publish command to non-existent computer', [
                'computer_id' => $computerId,
                'room_id' => $roomId,
            ]);

            return false;
        }

        // Create command record with pending status
        $command = Command::create([
            'type' => $commandType,
            'computer_id' => $computerId,
            'status' => CommandStatus::PENDING,
        ]);

        // Check if RabbitMQ is available
        if (! $this->rabbitMQService->isAvailable()) {
            Log::warning('RabbitMQ unavailable, command saved to database for later processing', [
                'command_id' => $command->id,
                'computer_id' => $computerId,
            ]);

            // Set status to queued for later processing
            $command->status = CommandStatus::QUEUED;
            $command->save();

            // Dispatch event for alternate processing if needed
            Event::dispatch('command.queued', $command);

            // Command is created but will be processed later
            return true;
        }

        // Create message payload
        $payload = [
            'command_id' => $command->id,
            'type' => $commandType->value,
            'timestamp' => time(),
        ];

        // Publish directly to the specific agent queue using MAC address
        $published = $this->rabbitMQService->publishToAgent(
            macAddress: $computer->mac_address,
            payload: $payload
        );

        // Update command status based on publishing result
        if (! $published) {
            $command->status = CommandStatus::QUEUED;
            $command->save();

            // Dispatch event for alternate processing
            Event::dispatch('command.queued', $command);

            Log::info('Command queued for later delivery due to RabbitMQ issue', [
                'command_id' => $command->id,
                'computer_id' => $computerId,
            ]);
        } else {
            $command->status = CommandStatus::SENT;
            $command->save();

            Log::info('Command published successfully to agent', [
                'command_id' => $command->id,
                'computer_id' => $computerId,
                'mac_address' => $computer->mac_address,
            ]);
        }

        return true;
    }

    /**
     * Publish command to multiple specific computers
     *
     * @param  string  $roomId  Room identifier
     * @param  array<string>  $computerIds  List of computer IDs
     * @param  CommandType  $commandType  Type of command
     * @return bool Success status
     */
    private function publishToMultipleComputers(
        string $roomId,
        array $computerIds,
        CommandType $commandType,
    ): bool {
        // Get all valid computers from the room
        $computers = Computer::query()
            ->where('room_id', $roomId)
            ->whereIn('id', $computerIds)
            ->get();

        Log::info('Publishing command to multiple computers', [
            'room_id' => $roomId,
            'computers' => $computers,
        ]);

        if ($computers->isEmpty()) {
            Log::warning('No valid computers found for multi-computer command', [
                'room_id' => $roomId,
                'computer_ids' => $computerIds,
            ]);

            return false;
        }

        $allSuccess = true;

        // Process each computer individually
        foreach ($computers as $computer) {
            $result = $this->publishToSingleComputer($roomId, $computer->id, $commandType);
            if (! $result) {
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }

    /**
     * Publish command to all computers in a room
     *
     * @param  Room  $room  Target room
     * @param  CommandType  $commandType  Type of command
     * @param  array<string, mixed>  $params  Additional parameters
     * @return bool Success status
     */
    private function publishToAllComputers(Room $room, CommandType $commandType, array $params = []): bool
    {
        // Get all computers in the room
        $computerCount = Computer::query()
            ->where('room_id', $room->id)
            ->where('status', '!=', 'offline')
            ->count();

        if ($computerCount === 0) {
            Log::warning('No online computers found in room for command broadcast', [
                'room_id' => $room->id,
            ]);

            return false;
        }

        // Create a single room-level command record
        $command = Command::create([
            'type' => $commandType,
            'room_id' => $room->id,
            'status' => CommandStatus::PENDING,
            // 'params' => $params,
        ]);

        // Check if RabbitMQ is available
        if (! $this->rabbitMQService->isAvailable()) {
            Log::warning('RabbitMQ unavailable, room command saved to database for later processing', [
                'command_id' => $command->id,
                'room_id' => $room->id,
            ]);

            // Set status to queued for later processing
            $command->status = CommandStatus::QUEUED;
            $command->save();

            // Dispatch event for alternate processing if needed
            Event::dispatch('command.queued', $command);

            return true;
        }

        // Create message payload
        $payload = [
            'command_id' => $command->id,
            'type' => $commandType->value,
            // 'params' => $params,
            'timestamp' => time(),
            'room_id' => $room->id,
        ];

        // Publish to the room using the cmd.direct exchange
        $published = $this->rabbitMQService->publishToRoom(
            roomId: $room->id,
            payload: $payload
        );

        // Update command status based on publishing result
        if (! $published) {
            $command->status = CommandStatus::QUEUED;
            $command->save();

            // Dispatch event for alternate processing
            Event::dispatch('command.queued', $command);

            Log::info('Room command queued for later delivery due to RabbitMQ issue', [
                'command_id' => $command->id,
                'room_id' => $room->id,
            ]);
        } else {
            $command->status = CommandStatus::SENT;
            $command->save();

            Log::info('Command published successfully to room', [
                'command_id' => $command->id,
                'room_id' => $room->id,
            ]);
        }

        return true;
    }
}

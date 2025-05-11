<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CommandType;
use App\Models\Computer;
use App\Services\RabbitMQService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PublishComputerCommandAction
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        private readonly RabbitMQService $rabbitMQService
    ) {}

    /**
     * Handle the command publishing to a specific computer
     *
     * @param  Authenticatable  $user  User initiating the command
     * @param  string  $computerId  The target computer ID
     * @param  CommandType  $commandType  Type of command to publish
     * @param  array<string, mixed>  $params  Additional parameters for the command
     * @return bool Whether the command was published successfully
     */
    public function handle(
        Authenticatable $user,
        string $computerId,
        CommandType $commandType,
        array $params = []
    ): bool {
        try {
            $computer = Computer::query()->find($computerId);

            if (! $computer) {
                Log::warning('Attempted to publish command to non-existent computer', [
                    'computer_id' => $computerId,
                    'user_id' => $user->id,
                ]);

                return false;
            }

            // Enhance params with user information
            $enhancedParams = array_merge($params, [
                'initiated_by' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);

            // Create message payload
            $payload = [
                'type' => $commandType->value,
                'params' => $enhancedParams,
                'timestamp' => time(),
            ];

            // Use direct agent publishing
            $result = $this->rabbitMQService->publishToAgent(
                macAddress: $computer->mac_address,
                payload: $payload
            );

            if ($result) {
                Log::info('Command published to computer', [
                    'computer_id' => $computer->id,
                    'mac_address' => $computer->mac_address,
                    'command_type' => $commandType->value,
                    'user_id' => $user->id,
                ]);
            } else {
                Log::error('Failed to publish command to computer', [
                    'computer_id' => $computer->id,
                    'mac_address' => $computer->mac_address,
                    'command_type' => $commandType->value,
                    'user_id' => $user->id,
                ]);
            }

            return $result;
        } catch (Throwable $e) {
            Log::error('Error publishing command to computer', [
                'error' => $e->getMessage(),
                'computer_id' => $computerId,
                'command_type' => $commandType->value,
                'user_id' => $user->id,
            ]);

            return false;
        }
    }
}

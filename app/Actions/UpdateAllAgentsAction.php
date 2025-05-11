<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CommandType;
use App\Models\Computer;
use App\Models\User;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class UpdateAllAgentsAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        protected RabbitMQService $rabbitMQService
    ) {}

    /**
     * Handle the update all agents command.
     *
     * @param  User  $user  The user initiating the update
     * @param  array<string, mixed>  $data  Update parameters (version, force, restart_after)
     * @return array<string, mixed> Result information
     */
    public function handle(User $user, array $data): array
    {
        // Find all computers that are online or idle
        $computers = Computer::query()
            ->whereIn('status', ['online', 'idle'])
            ->get();

        // If no computers are available, return early
        if ($computers->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No online computers found in the system',
                'total' => 0,
                'updated' => 0,
            ];
        }

        // Check RabbitMQ availability
        if (! $this->rabbitMQService->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Message broker is not available',
                'total' => $computers->count(),
                'updated' => 0,
                'failed' => $computers->count(),
            ];
        }

        // Prepare update command payload
        $payload = [
            'type' => CommandType::UPDATE->value,
            'initiated_by' => $user->id,
            'timestamp' => now()->toIso8601String(),
            'is_broadcast' => true,
            'params' => [
                'version' => $data['version'] ?? null,
                'force' => $data['force'] ?? false,
                'restart_after' => $data['restart_after'] ?? false,
            ],
        ];

        // Log the update action
        Log::info('Initiating system-wide agent update', [
            'user_id' => $user->id,
            'computer_count' => $computers->count(),
            'version' => $data['version'] ?? 'latest',
            'force' => $data['force'] ?? false,
            'restart_after' => $data['restart_after'] ?? false,
        ]);

        // Send broadcast message to all agents
        $result = $this->rabbitMQService->publishBroadcast($payload);

        if ($result) {
            return [
                'success' => true,
                'message' => "Update initiated for {$computers->count()} computers",
                'total' => $computers->count(),
                'updated' => $computers->count(),
                'failed' => 0,
            ];
        }

        // If broadcast failed, return error
        return [
            'success' => false,
            'message' => 'Failed to initiate system-wide agent update',
            'total' => $computers->count(),
            'updated' => 0,
            'failed' => $computers->count(),
        ];
    }
}

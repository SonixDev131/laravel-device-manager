<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\RabbitMQ\RabbitMQService;

final class PublishComputerCommandAction
{
    public function __construct(
        private readonly RabbitMQService $rabbitMQService
    ) {}

    /**
     * Send command to a specific computer
     */
    public function publishCommandToComputer(string $computerId, string $roomId, string $commandType): bool
    {
        return $this->rabbitMQService->sendCommandToComputer($computerId, $roomId, $commandType);
    }

    /**
     * Send command to multiple computers in a room
     */
    public function publishCommandToMultipleComputers(array $computerIds, string $roomId, string $commandType): array
    {
        $results = [];

        foreach ($computerIds as $computerId) {
            $results[$computerId] = $this->publishCommandToComputer($computerId, $roomId, $commandType);
        }

        return $results;
    }

    /**
     * Send command to all computers in a room
     */
    public function publishCommandToRoom(string $roomId, string $commandType): bool
    {
        return $this->rabbitMQService->sendCommandToRoom($roomId, $commandType);
    }
}

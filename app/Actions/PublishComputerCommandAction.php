<?php

namespace App\Actions;

use App\Services\RabbitMQService;

class PublishComputerCommandAction
{
    public function __construct(
        private RabbitMQService $rabbitMQService
    ) {}

    public function handle(string $computerId, string $roomId, string $commandType)
    {
        // Publish the command to RabbitMQ
        $this->rabbitMQService->sendCommandToComputer($computerId, $roomId, $commandType);
    }
}

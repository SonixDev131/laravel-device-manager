<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

/**
 * Interface for RabbitMQ configuration
 */
interface RabbitMQConfigInterface
{
    /**
     * Get all exchange configurations
     *
     * @return array<Exchange>
     */
    public function getExchanges(): array;

    /**
     * Get the command exchange name
     */
    public function getCommandExchange(): string;

    /**
     * Get the status exchange name
     */
    public function getStatusExchange(): string;

    /**
     * Get the command routing key pattern
     */
    public function getCommandRoutingKey(): string;

    /**
     * Get the room broadcast routing key pattern
     */
    public function getRoomBroadcastRoutingKey(): string;

    /**
     * Get the status routing key pattern
     */
    public function getStatusRoutingKey(): string;

    /**
     * Get the status queue name
     */
    public function getStatusQueue(): string;
}

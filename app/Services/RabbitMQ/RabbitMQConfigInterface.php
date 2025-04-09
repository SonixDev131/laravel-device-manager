<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

/**
 * Interface for RabbitMQ configuration
 */
interface RabbitMQConfigInterface
{
    /**
     * Get exchanges configuration
     *
     * @return array<ExchangeConfig>
     */
    public function getExchanges(): array;

    /**
     * Get command exchange name
     */
    public function getCommandExchange(): string;

    /**
     * Get status exchange name
     */
    public function getStatusExchange(): string;

    /**
     * Get command routing key pattern
     * Should contain placeholders like {room}, {computer}
     */
    public function getCommandRoutingKey(): string;

    /**
     * Get room broadcast routing key pattern
     * Should contain placeholder like {room}
     */
    public function getRoomBroadcastRoutingKey(): string;

    /**
     * Get status routing key
     */
    public function getStatusRoutingKey(): string;

    /**
     * Get status queue name
     */
    public function getStatusQueue(): string;
}

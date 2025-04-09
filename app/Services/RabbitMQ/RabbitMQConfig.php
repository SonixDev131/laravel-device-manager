<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

/**
 * Configuration for RabbitMQ service
 */
class RabbitMQConfig implements RabbitMQConfigInterface
{
    /**
     * @var array<string, ExchangeConfig>
     */
    private array $exchanges = [];

    public function __construct()
    {
        // Initialize default exchanges
        $this->exchanges = [
            'commands' => new ExchangeConfig(
                name: config('rabbitmq.exchanges.commands.name', 'commands'),
                type: config('rabbitmq.exchanges.commands.type', 'topic'),
                durable: config('rabbitmq.exchanges.commands.durable', true),
                autoDelete: config('rabbitmq.exchanges.commands.auto_delete', false)
            ),
            'status' => new ExchangeConfig(
                name: config('rabbitmq.exchanges.status.name', 'status'),
                type: config('rabbitmq.exchanges.status.type', 'topic'),
                durable: config('rabbitmq.exchanges.status.durable', true),
                autoDelete: config('rabbitmq.exchanges.status.auto_delete', false)
            ),
        ];
    }

    /**
     * Get all configured exchanges
     */
    public function getExchanges(): array
    {
        return array_values($this->exchanges);
    }

    /**
     * Get command exchange name
     */
    public function getCommandExchange(): string
    {
        return $this->exchanges['commands']->name;
    }

    /**
     * Get status exchange name
     */
    public function getStatusExchange(): string
    {
        return $this->exchanges['status']->name;
    }

    /**
     * Get command routing key pattern
     * Default: room.{room}.computer.{computer}
     */
    public function getCommandRoutingKey(): string
    {
        return config('rabbitmq.routing_keys.command', 'room.{room}.computer.{computer}');
    }

    /**
     * Get room broadcast routing key pattern
     * Default: room.{room}.all
     */
    public function getRoomBroadcastRoutingKey(): string
    {
        return config('rabbitmq.routing_keys.room_broadcast', 'room.{room}.all');
    }

    /**
     * Get status routing key
     * Default: status.#
     */
    public function getStatusRoutingKey(): string
    {
        return config('rabbitmq.routing_keys.status', 'status.#');
    }

    /**
     * Get status queue name
     * Default: status_queue
     */
    public function getStatusQueue(): string
    {
        return config('rabbitmq.queues.status', 'status_queue');
    }
}

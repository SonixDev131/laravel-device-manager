<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

use Illuminate\Contracts\Config\Repository;

/**
 * RabbitMQ configuration implementation using Laravel config
 */
final readonly class RabbitMQConfig implements RabbitMQConfigInterface
{
    public function __construct(private Repository $config) {}

    /**
     * Get the list of exchanges to declare
     *
     * @return array<Exchange>
     */
    public function getExchanges(): array
    {
        return [
            new Exchange(
                name: $this->config->get('rabbitmq.exchanges.commands.name', 'unilab.commands'),
                type: $this->config->get('rabbitmq.exchanges.commands.type', 'topic'),
                durable: $this->config->get('rabbitmq.exchanges.commands.durable', true),
                autoDelete: $this->config->get('rabbitmq.exchanges.commands.auto_delete', false)
            ),
            new Exchange(
                name: $this->config->get('rabbitmq.exchanges.status.name', 'unilab.status'),
                type: $this->config->get('rabbitmq.exchanges.status.type', 'topic'),
                durable: $this->config->get('rabbitmq.exchanges.status.durable', true),
                autoDelete: $this->config->get('rabbitmq.exchanges.status.auto_delete', false)
            ),
        ];
    }

    /**
     * Get the exchange name for commands
     */
    public function getCommandExchange(): string
    {
        return $this->config->get('rabbitmq.exchanges.commands.name', 'unilab.commands');
    }

    /**
     * Get the routing key pattern for computer commands
     */
    public function getCommandRoutingKey(): string
    {
        return $this->config->get('rabbitmq.routing_keys.commands', 'command.room_{room}.computer_{computer}');
    }

    /**
     * Get the routing key pattern for room broadcast commands
     */
    public function getRoomBroadcastRoutingKey(): string
    {
        return $this->config->get('rabbitmq.routing_keys.room_broadcast', 'command.room_{room}.broadcast');
    }

    /**
     * Get the exchange name for status updates
     */
    public function getStatusExchange(): string
    {
        return $this->config->get('rabbitmq.exchanges.status.name', 'unilab.status');
    }

    /**
     * Get the queue name for status updates
     */
    public function getStatusQueue(): string
    {
        return $this->config->get('rabbitmq.queues.status', 'status_updates');
    }

    /**
     * Get the routing key pattern for status updates
     */
    public function getStatusRoutingKey(): string
    {
        return $this->config->get('rabbitmq.routing_keys.status', 'status.#');
    }
}

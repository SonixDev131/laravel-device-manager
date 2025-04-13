<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

use Illuminate\Support\Facades\Config;

 // Use Facade for cleaner access

/**
 * Configuration accessor for RabbitMQ service based on config/rabbitmq.php
 */
class RabbitMQConfig implements RabbitMQConfigInterface
{
    /**
     * Get all configured exchanges as ExchangeConfig objects.
     *
     * @return array<int, ExchangeConfig>
     */
    public function getExchanges(): array
    {
        /** @var array<string, array{name: string, type: string, durable: bool, auto_delete: bool, passive: bool}> $exchangesConfig */
        $exchangesConfig = Config::get('rabbitmq.exchanges', []);

        $exchanges = [];
        foreach ($exchangesConfig as $config) {
            $exchanges[] = new ExchangeConfig(
                name: $config['name'],
                type: $config['type'],
                durable: $config['durable'],
                autoDelete: $config['auto_delete']
                // passive: $config['passive'] // Add if needed in ExchangeConfig
            );
        }

        return $exchanges;
    }

    /**
     * Get command exchange name.
     */
    public function getCommandExchange(): string
    {
        return Config::get('rabbitmq.exchanges.commands.name', '');
    }

    /**
     * Get status exchange name.
     */
    public function getStatusExchange(): string
    {
        return Config::get('rabbitmq.exchanges.status.name', '');
    }

    /**
     * Get command routing key pattern for specific computer.
     */
    public function getCommandRoutingKey(): string
    {
        return Config::get('rabbitmq.routing_keys.command_computer', '');
    }

    /**
     * Get room broadcast routing key pattern.
     */
    public function getRoomBroadcastRoutingKey(): string
    {
        return Config::get('rabbitmq.routing_keys.command_room_broadcast', '');
    }

    /**
     * Get status routing key pattern.
     */
    public function getStatusRoutingKey(): string
    {
        return Config::get('rabbitmq.routing_keys.status_updates', '');
    }

    /**
     * Get status queue name.
     */
    public function getStatusQueue(): string
    {
        return Config::get('rabbitmq.queues.computer_status', '');
    }

    // Add methods for other config values if needed (e.g., connection details)
    public function getConnectionConfig(): array
    {
        /** @var array{host: string, port: int, user: string, password: string, vhost: string} */
        return Config::get('rabbitmq.connection', []);
    }
}

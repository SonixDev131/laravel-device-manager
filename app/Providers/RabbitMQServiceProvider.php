<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RabbitMQ\RabbitMQConfig;
use App\Services\RabbitMQ\RabbitMQConfigInterface;
use App\Services\RabbitMQ\RabbitMQService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register RabbitMQ Config - Reads from config/rabbitmq.php
        $this->app->singleton(RabbitMQConfigInterface::class, function (): RabbitMQConfig {
            return new RabbitMQConfig;
        });

        // Register AMQPStreamConnection using the new config structure
        $this->app->singleton(AMQPStreamConnection::class, function (): AMQPStreamConnection {
            return new AMQPStreamConnection(
                host: config('rabbitmq.connection.host', 'localhost'),
                port: (int) config('rabbitmq.connection.port', 5672),
                user: config('rabbitmq.connection.user', 'guest'),
                password: config('rabbitmq.connection.password', 'guest'),
                vhost: config('rabbitmq.connection.vhost', '/'),
                insist: false,
                login_method: 'AMQPLAIN',
                login_response: null,
                locale: 'en_US',
                // Consider adding these to config if needed
                connection_timeout: (float) config('rabbitmq.connection.connection_timeout', 3.0),
                read_write_timeout: (float) config('rabbitmq.connection.read_write_timeout', 3.0),
                context: null,
                keepalive: (bool) config('rabbitmq.connection.keepalive', false),
                heartbeat: (int) config('rabbitmq.connection.heartbeat', 0)
            );
        });

        // Register RabbitMQService
        $this->app->singleton(RabbitMQService::class, function (Application $app): RabbitMQService {
            return new RabbitMQService(
                $app->make(AMQPStreamConnection::class),
                $app->make(RabbitMQConfigInterface::class)
            );
        });
    }
}

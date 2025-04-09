<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RabbitMQ\RabbitMQConfig;
use App\Services\RabbitMQ\RabbitMQConfigInterface;
use App\Services\RabbitMQ\RabbitMQService;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register RabbitMQ Config
        $this->app->singleton(RabbitMQConfigInterface::class, function () {
            return new RabbitMQConfig();
        });

        // Register AMQPStreamConnection
        $this->app->singleton(AMQPStreamConnection::class, function () {
            return new AMQPStreamConnection(
                host: config('rabbitmq.host', 'localhost'),
                port: (int)config('rabbitmq.port', 5672),
                user: config('rabbitmq.user', 'guest'),
                password: config('rabbitmq.password', 'guest'),
                vhost: config('rabbitmq.vhost', '/'),
                insist: false,
                login_method: 'AMQPLAIN',
                login_response: null,
                locale: 'en_US',
                connection_timeout: (float)config('rabbitmq.connection_timeout', 3.0),
                read_write_timeout: (float)config('rabbitmq.read_write_timeout', 3.0),
                context: null,
                keepalive: false,
                heartbeat: (int)config('rabbitmq.heartbeat', 0)
            );
        });

        // Register RabbitMQService
        $this->app->singleton(RabbitMQService::class, function ($app) {
            return new RabbitMQService(
                $app->make(AMQPStreamConnection::class),
                $app->make(RabbitMQConfigInterface::class)
            );
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RabbitMQ\RabbitMQConfig;
use App\Services\RabbitMQ\RabbitMQConfigInterface;
use App\Services\RabbitMQService;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register RabbitMQConfig as implementation of RabbitMQConfigInterface
        $this->app->singleton(RabbitMQConfigInterface::class, RabbitMQConfig::class);

        // Register AMQPStreamConnection
        $this->app->singleton(AMQPStreamConnection::class, function ($app) {
            return new AMQPStreamConnection(
                $app['config']->get('rabbitmq.host'),
                $app['config']->get('rabbitmq.port'),
                $app['config']->get('rabbitmq.user'),
                $app['config']->get('rabbitmq.password'),
                $app['config']->get('rabbitmq.vhost'),
                false,
                'AMQPLAIN',
                null,
                'en_US',
                $app['config']->get('rabbitmq.timeout'),
                $app['config']->get('rabbitmq.timeout'),
                null,
                false,
                30
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

    public function boot(): void
    {
        //
    }
}

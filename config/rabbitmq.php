<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for connecting to RabbitMQ.
    |
    */

    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'username' => env('RABBITMQ_USERNAME', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Exchange Settings
    |--------------------------------------------------------------------------
    |
    | These settings define the exchanges used for different messaging patterns.
    |
    */

    'exchanges' => [
        'direct' => [
            'name' => 'cmd.direct',
            'type' => 'direct',
            'passive' => false,
            'durable' => true,
            'auto_delete' => false,
        ],
        'broadcast' => [
            'name' => 'broadcast.fanout',
            'type' => 'fanout',
            'passive' => false,
            'durable' => true,
            'auto_delete' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Consumer Settings
    |--------------------------------------------------------------------------
    |
    | These settings define the behavior of message consumers.
    |
    */

    'consumer' => [
        'prefetch_count' => 1,
        'reconnect_delay' => 5, // seconds
        'max_retries' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for messages published to RabbitMQ.
    |
    */

    'message' => [
        'content_type' => 'application/json',
        'delivery_mode' => 2, // persistent
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Types
    |--------------------------------------------------------------------------
    |
    | Mapping of command types to their internal representations.
    |
    */

    'command_types' => [
        'update' => 'agent.update',
        'restart' => 'agent.restart',
        'shutdown' => 'agent.shutdown',
        'execute' => 'agent.execute',
        'status' => 'agent.status',
    ],
];

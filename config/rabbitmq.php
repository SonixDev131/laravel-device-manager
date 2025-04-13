<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Settings
    |--------------------------------------------------------------------------
    */
    'connection' => [
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Exchanges Configuration
    |--------------------------------------------------------------------------
    | Define all exchanges used by the application.
    */
    'exchanges' => [
        'commands' => [
            'name' => 'unilab.commands',
            'type' => 'topic', // e.g., direct, topic, fanout, headers
            'durable' => true,
            'auto_delete' => false,
            'passive' => false, // Add passive flag if needed
        ],
        'status' => [
            'name' => 'unilab.status',
            'type' => 'topic',
            'durable' => true,
            'auto_delete' => false,
            'passive' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Queues Configuration
    |--------------------------------------------------------------------------
    | Define all queues used by the application.
    */
    'queues' => [
        'computer_status' => 'unilab.computer.status',
        'computer_commands' => 'unilab.computer.commands',
        // Add other queues as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Routing Keys Configuration
    |--------------------------------------------------------------------------
    | Define routing key patterns.
    */
    'routing_keys' => [
        'command_computer' => 'room.{room}.computer.{computer}',
        'command_room_broadcast' => 'room.{room}.all',
        'status_updates' => 'status.#',
    ],

    /*
    |--------------------------------------------------------------------------
    | Consumer Settings
    |--------------------------------------------------------------------------
    */
    'consumer' => [
        'prefetch_count' => (int) env('RABBITMQ_PREFETCH_COUNT', 10),
        'timeout' => (int) env('RABBITMQ_TIMEOUT', 0), // 0 means wait indefinitely
    ],
];

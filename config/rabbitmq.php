<?php

declare(strict_types=1);

return [
    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
    'timeout' => env('RABBITMQ_TIMEOUT', 10.0),

    // Define exchanges in a more structured way
    'exchanges' => [
        'commands' => [
            'name' => 'unilab.commands',
            'type' => 'topic',
            'durable' => true,
            'auto_delete' => false,
        ],
        'status' => [
            'name' => 'unilab.status',
            'type' => 'topic',
            'durable' => true,
            'auto_delete' => false,
        ],
    ],

    // Define queues
    'queues' => [
        'status' => 'status_updates',
    ],

    // Define routing keys for different message types
    'routing_keys' => [
        'commands' => 'command.room_{room}.computer_{computer}',
        'room_broadcast' => 'command.room_{room}.broadcast',
        'status' => 'status.#',
        'updates' => 'updates.{os}.{version}',
    ],
];

<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

/**
 * Configuration data for a RabbitMQ exchange
 */
class ExchangeConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $type, // direct, topic, fanout, headers
        public readonly bool $durable = true,
        public readonly bool $autoDelete = false
    ) {
    }
}

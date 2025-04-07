<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ;

/**
 * Data class representing a RabbitMQ exchange configuration
 */
final class Exchange
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $durable,
        public readonly bool $autoDelete,
    ) {}
}

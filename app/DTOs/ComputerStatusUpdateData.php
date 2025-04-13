<?php

declare(strict_types=1);

namespace App\DTOs;

final class ComputerStatusUpdateData
{
    public function __construct(
        public readonly ?string $computerId,
        public readonly ?string $status
    ) {}
}

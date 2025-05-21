<?php

declare(strict_types=1);

namespace App\Enums;

enum CommandStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SENT = 'sent';
    case FAILED = 'failed';
    case COMPLETED = 'completed';
}

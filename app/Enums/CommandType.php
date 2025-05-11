<?php

declare(strict_types=1);

namespace App\Enums;

enum CommandType: string
{
    case LOCK = 'LOCK';
    case UPDATE = 'agent.update';
    case RESTART = 'agent.restart';
    case SHUTDOWN = 'agent.shutdown';
    case EXECUTE = 'agent.execute';
    case STATUS = 'agent.status';
}

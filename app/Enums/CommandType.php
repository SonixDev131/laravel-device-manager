<?php

declare(strict_types=1);

namespace App\Enums;

enum CommandType: string
{
    case LOCK = 'LOCK';
    case UPDATE = 'UPDATE';
    case RESTART = 'RESTART';
    case SHUTDOWN = 'SHUTDOWN';
    case EXECUTE = 'EXECUTE';
    case STATUS = 'STATUS';
    case FIREWALL_ON = 'FIREWALL_ON';
    case FIREWALL_OFF = 'FIREWALL_OFF';
}

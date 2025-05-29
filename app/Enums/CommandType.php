<?php

declare(strict_types=1);

namespace App\Enums;

enum CommandType: string
{
    case LOCK = 'LOCK';
    case MESSAGE = 'MESSAGE';
    case SCREENSHOT = 'SCREENSHOT';
    case UPDATE = 'UPDATE';
    case RESTART = 'RESTART';
    case SHUTDOWN = 'SHUTDOWN';
    case LOG_OUT = 'LOG_OUT';
    case CUSTOM = 'CUSTOM';
    case EXECUTE = 'EXECUTE';
    case STATUS = 'STATUS';
    case FIREWALL_ON = 'FIREWALL_ON';
    case FIREWALL_OFF = 'FIREWALL_OFF';
    case BLOCK_WEBSITE = 'BLOCK_WEBSITE';
}

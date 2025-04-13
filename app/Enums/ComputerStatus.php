<?php

declare(strict_types=1);

namespace App\Enums;

enum ComputerStatus: string
{
    case ONLINE = 'online';           // Computer is active and responding
    case OFFLINE = 'offline';         // Computer is not responding
    case SHUTTING_DOWN = 'shutting_down'; // Computer is in the process of shutting down
    case IDLE = 'idle';               // Computer is on but no user is logged in
    case MAINTENANCE = 'maintenance'; // Computer is undergoing maintenance

    /**
     * Get a human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::OFFLINE => 'Offline',
            self::SHUTTING_DOWN => 'Shutting Down',
            self::IDLE => 'Idle',
            self::MAINTENANCE => 'Maintenance',
        };
    }

    /**
     * Check if the status indicates the computer is available
     */
    public function isAvailable(): bool
    {
        return match ($this) {
            self::ONLINE, self::IDLE => true,
            default => false,
        };
    }
}

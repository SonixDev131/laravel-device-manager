<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComputerStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperComputer
 */
final class Computer extends Model
{
    use HasFactory, HasUuids;

    /**
     * Check if computer has timed out (is considered offline)
     */
    public function hasTimedOut(int $timeoutInMinutes = 3): bool
    {
        // If it's never been seen, it's considered offline
        if (! $this->last_seen_at) {
            return true;
        }

        // Check if the last_seen_at timestamp is older than the timeout period
        return $this->last_seen_at->addMinutes($timeoutInMinutes)->isPast();
    }

    /**
     * Update the computer's status based on the heartbeat timeout
     */
    public function updateStatusBasedOnTimeout(int $timeoutInMinutes = 3): bool
    {
        // Only update status if the current status is not MAINTENANCE or SHUTTING_DOWN
        if ($this->status === ComputerStatus::MAINTENANCE || $this->status === ComputerStatus::SHUTTING_DOWN) {
            return false;
        }

        $wasChanged = false;

        // If computer has timed out, mark it as offline
        if ($this->hasTimedOut($timeoutInMinutes)) {
            // Only update if current status is not already OFFLINE
            if ($this->status !== ComputerStatus::OFFLINE) {
                $this->status = ComputerStatus::OFFLINE;
                $wasChanged = true;
            }
        } elseif ($this->status === ComputerStatus::OFFLINE) {
            // If it hasn't timed out but status is OFFLINE, change to ONLINE
            $this->status = ComputerStatus::ONLINE;
            $wasChanged = true;
        }

        if ($wasChanged) {
            $this->save();
        }

        return $wasChanged;
    }

    /**
     * Update the computer's status and last_seen_at timestamp based on a heartbeat from the agent
     */
    public function updateFromHeartbeat(string $status, array $metrics = []): bool
    {
        // Map agent status to ComputerStatus enum
        $computerStatus = match ($status) {
            'online' => ComputerStatus::ONLINE,
            'offline' => ComputerStatus::OFFLINE,
            'shutting_down', 'restarting' => ComputerStatus::SHUTTING_DOWN,
            'idle' => ComputerStatus::IDLE,
            default => $this->status, // Keep current status if unknown
        };

        // Don't change status if computer is in maintenance mode
        if ($this->status === ComputerStatus::MAINTENANCE) {
            // But still update last_seen_at
            $this->last_seen_at = now();
            $this->save();

            return false;
        }

        $changed = $this->status !== $computerStatus;

        // Update the status and last_seen_at
        $this->status = $computerStatus;
        $this->last_seen_at = now();

        // Store system metrics if provided
        if (! empty($metrics)) {
            $this->system_metrics = $metrics;
        }

        $this->save();

        return $changed;
    }

    /**
     * Get the room that the computer belongs to
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the commands that have been assigned to this computer
     */
    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ComputerStatus::class,
            'last_seen_at' => 'datetime',
            'system_metrics' => 'array',
        ];
    }
}

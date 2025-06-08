<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComputerStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id UUID primary key
 * @property ComputerStatus $status Current operational status
 * @property string|null $room_id Foreign key to the room
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $last_heartbeat_at Last time computer sent heartbeat
 * @property-read Room $room The room this computer is located in
 * @property-read Collection<int, Command> $commands Commands assigned to this computer
 * @property-read Collection<int, Metric> $metrics Metrics collected from this computer
 *
 * @method static \Database\Factories\ComputerFactory factory($count = null, $state = [])
 *
 * @property string $mac_address
 * @property string $hostname
 * @property int $pos_row
 * @property int $pos_col
 * @property-read int|null $commands_count
 * @property-read int|null $metrics_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereMacAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer wherePosCol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer wherePosRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Computer extends Model
{
    use HasFactory, HasUuids;

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'status' => ComputerStatus::class,
    ];

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** @return HasMany<Command, $this> */
    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }

    /** @return HasMany<Metric, $this> */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    /** @return HasOne<Metric, $this> */
    public function latestMetric(): HasOne
    {
        return $this->hasOne(Metric::class)->latest('created_at')->limit(1);
    }

    /** @return HasMany<Screenshot, $this> */
    public function screenshots(): HasMany
    {
        return $this->hasMany(Screenshot::class);
    }

    /**
     * Check if computer has timed out based on last heartbeat.
     *
     * @param  int  $timeoutMinutes  Number of minutes after which computer is considered timed out
     */
    public function hasTimedOut(int $timeoutMinutes = 5): bool
    {
        if ($this->last_heartbeat_at === null) {
            return true;
        }

        return $this->last_heartbeat_at->diffInMinutes(now()) > $timeoutMinutes;
    }

    /**
     * Update computer status based on timeout.
     *
     * @param  int  $timeoutMinutes  Number of minutes after which computer is considered timed out
     * @return bool True if status was changed, false otherwise
     */
    public function updateStatusBasedOnTimeout(int $timeoutMinutes = 5): bool
    {
        // Don't change maintenance status
        if ($this->status === ComputerStatus::MAINTENANCE) {
            return false;
        }

        $hasTimedOut = $this->hasTimedOut($timeoutMinutes);
        $oldStatus = $this->status;

        if ($hasTimedOut && $this->status === ComputerStatus::ONLINE) {
            $this->status = ComputerStatus::OFFLINE;
            $this->save();

            return true;
        }

        if (! $hasTimedOut && $this->status === ComputerStatus::OFFLINE) {
            $this->status = ComputerStatus::ONLINE;
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Update computer from heartbeat.
     *
     * @param  string  $status  Status from heartbeat
     * @return bool True if status was changed, false otherwise
     */
    public function updateFromHeartbeat(string $status): bool
    {
        $this->last_heartbeat_at = now();

        // Don't change maintenance status
        if ($this->status === ComputerStatus::MAINTENANCE) {
            $this->save();

            return false;
        }

        $oldStatus = $this->status;
        $this->status = ComputerStatus::from($status);
        $this->save();

        return $oldStatus !== $this->status;
    }
}

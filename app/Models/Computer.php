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
        return $this->hasOne(Metric::class)->latest()->limit(1);
    }
}

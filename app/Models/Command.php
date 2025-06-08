<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $computer_id
 * @property string|null $room_id
 * @property string $type
 * @property string|null $params
 * @property string $status
 * @property string|null $error
 * @property bool $is_group_command
 * @property string|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Computer|null $computer
 * @property-read Room|null $room
 *
 * @method static \Database\Factories\CommandFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereIsGroupCommand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Command extends Model
{
    use HasFactory, HasUuids;

    protected $casts = [
        'is_group_command' => 'boolean',
        'completed_at' => 'datetime',
        'params' => 'array',
    ];

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(Screenshot::class);
    }
}

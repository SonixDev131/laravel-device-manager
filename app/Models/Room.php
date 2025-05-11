<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Room model representing a physical or virtual room containing computers
 *
 * @property string $id UUID primary key
 * @property string $name Room name
 * @property int $grid_rows Number of rows in the grid
 * @property int $grid_cols Number of columns in the grid
 * @property Carbon $created_at When the room was created
 * @property Carbon $updated_at When the room was last updated
 * @property-read Collection<int, Computer> $computers Computers in this room
 * @property-read int|null $computers_count
 * @method static \Database\Factories\RoomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereGridCols($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereGridRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class Room extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the computers that belong to this room
     *
     * @return HasMany<Computer>
     */
    public function computers(): HasMany
    {
        return $this->hasMany(Computer::class);
    }
}

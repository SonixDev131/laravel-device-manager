<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $computer_id
 * @property float $cpu_usage
 * @property int $memory_total
 * @property int $memory_used
 * @property int $disk_total
 * @property int $disk_used
 * @property int $uptime
 * @property string $platform
 * @property string $platform_version
 * @property string $hostname
 * @property-read Computer $computer
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereCpuUsage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereDiskTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereDiskUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereMemoryTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereMemoryUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric wherePlatformVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Metric whereUptime($value)
 *
 * @mixin \Eloquent
 */
class Metric extends Model
{
    use HasFactory, HasUuids;

    protected $casts = [
        'firewall_status' => 'array',
    ];

    /** @return BelongsTo<Computer, $this>  */
    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }
}

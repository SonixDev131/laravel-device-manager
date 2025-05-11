<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 *
 *
 * @property string $id
 * @property string $name
 * @property string $file_name
 * @property string $mime_type
 * @property string $path
 * @property string $disk
 * @property string $file_hash SHA256 hash of the version file
 * @property string $version Version number (e.g., 1.0.1)
 * @property bool $is_latest Is this the latest version?
 * @property int $size this the latest version?
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereFileHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereIsLatest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentPackage whereVersion($value)
 * @mixin \Eloquent
 */
final class AgentPackage extends Model
{
    use HasUuids;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return [
            'is_latest' => 'boolean',
        ];
    }
}

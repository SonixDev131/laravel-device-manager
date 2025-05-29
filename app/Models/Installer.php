<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property string $file_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property string $file_hash
 * @property bool $auto_install
 * @property string|null $install_args
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Installer query()
 *
 * @mixin \Eloquent
 */
final class Installer extends Model
{
    use HasUuids;

    /** @return array<string,string> */
    protected function casts(): array
    {
        return [
            'auto_install' => 'boolean',
        ];
    }
}

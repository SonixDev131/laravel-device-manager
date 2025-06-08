<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $command_id
 * @property string $computer_id
 * @property string $file_path
 * @property string $file_name
 * @property int $file_size
 * @property string $mime_type
 * @property Carbon|null $taken_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Command $command
 * @property-read Computer $computer
 */
final class Screenshot extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'command_id',
        'computer_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    /**
     * Get the full file URL for the screenshot
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }
}

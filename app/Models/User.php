<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\RolesEnum;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class User extends Authenticatable implements Authorizable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get all room assignments for this user.
     *
     * @return HasMany<UserRoomAssignment>
     */
    public function roomAssignments(): HasMany
    {
        return $this->hasMany(UserRoomAssignment::class);
    }

    /**
     * Get only active room assignments.
     *
     * @return HasMany<UserRoomAssignment>
     */
    public function activeRoomAssignments(): HasMany
    {
        return $this->roomAssignments()->active();
    }

    /**
     * Get assigned rooms through the assignment table.
     *
     * @return BelongsToMany<Room, User>
     */
    public function assignedRooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'user_room_assignments')
            ->withPivot(['is_active', 'assigned_at', 'expires_at'])
            ->withTimestamps();
    }

    /**
     * Get only active assigned rooms.
     *
     * @return BelongsToMany<Room, User>
     */
    public function activeAssignedRooms(): BelongsToMany
    {
        return $this->assignedRooms()
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('user_room_assignments.expires_at')
                    ->orWhere('user_room_assignments.expires_at', '>', now());
            });
    }

    /**
     * Check if user has access to a specific room.
     */
    public function hasAccessToRoom(string $roomId): bool
    {
        return $this->activeAssignedRooms()->where('rooms.id', $roomId)->exists();
    }

    public function getRedirectRoute(): string
    {
        return match ($this->getRoleNames()[0]) {
            RolesEnum::SUPERADMIN->value => route('rooms.index'),
            RolesEnum::TEACHER->value => route('teacher.rooms'),
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

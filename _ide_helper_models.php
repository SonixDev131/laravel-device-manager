<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string|null $computer_id
 * @property string|null $room_id
 * @property string $command_type
 * @property string|null $payload
 * @property string $status
 * @property bool $is_group_command
 * @property string|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Computer|null $computer
 * @property-read \App\Models\Room|null $room
 * @method static \Database\Factories\CommandFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereCommandType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereComputerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereIsGroupCommand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperCommand {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $room_id
 * @property string $mac_address
 * @property string $hostname
 * @property int $pos_row
 * @property int $pos_col
 * @property \App\Enums\ComputerStatus $status
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property array<array-key, mixed>|null $system_metrics System metrics reported by agent (CPU, RAM, etc.)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Command> $commands
 * @property-read int|null $commands_count
 * @property-read \App\Models\Room $room
 * @method static \Database\Factories\ComputerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereMacAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer wherePosCol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer wherePosRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereSystemMetrics($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Computer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperComputer {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property int $grid_rows
 * @property int $grid_cols
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Computer> $computers
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
	#[\AllowDynamicProperties]
	final class IdeHelperRoom {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperUser {}
}


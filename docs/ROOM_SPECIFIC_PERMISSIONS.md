# Room-Specific Permissions Implementation

This document explains how to implement room-specific permissions for teachers in the computer lab management system using Laravel and Spatie Permission package.

## Overview

The system implements a two-layer access control:

1. **Role-based permissions** (what actions a user can perform)
2. **Room-specific access** (which rooms a teacher can access)

## Architecture Components

### 1. Database Structure

#### User Room Assignments Table

```sql
CREATE TABLE user_room_assignments (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    room_id UUID REFERENCES rooms(id),
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMP,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. Models

#### UserRoomAssignment Model

```php
// app/Models/UserRoomAssignment.php
class UserRoomAssignment extends Model
{
    use HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}
```

#### User Model Extensions

```php
// app/Models/User.php
class User extends Authenticatable
{
    public function activeAssignedRooms(): BelongsToMany
    {
        return $this->assignedRooms()
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('user_room_assignments.expires_at')
                    ->orWhere('user_room_assignments.expires_at', '>', now());
            });
    }

    public function hasAccessToRoom(string $roomId): bool
    {
        return $this->activeAssignedRooms()->where('rooms.id', $roomId)->exists();
    }
}
```

### 3. Middleware

#### EnsureUserHasRoomAccess Middleware

```php
// app/Http/Middleware/EnsureUserHasRoomAccess.php
class EnsureUserHasRoomAccess
{
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();

        // SuperAdmins have access to all rooms
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Get room ID from route
        $roomId = $request->route('room')?->id ?? $request->route('roomId');

        // Check room access
        if (!$user->hasAccessToRoom($roomId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
```

### 4. Actions

#### AssignTeacherToRoomAction

```php
// app/Actions/AssignTeacherToRoomAction.php
class AssignTeacherToRoomAction
{
    public function handle(User $teacher, Room $room, ?Carbon $expiresAt = null): UserRoomAssignment
    {
        // Validate teacher role
        if (!$teacher->hasRole('teacher')) {
            throw new InvalidArgumentException('User must have teacher role');
        }

        return UserRoomAssignment::create([
            'user_id' => $teacher->id,
            'room_id' => $room->id,
            'is_active' => true,
            'assigned_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }
}
```

## Implementation Guide

### Step 1: Apply Middleware to Controllers

```php
// app/Http/Controllers/RoomController.php
class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('room.access')->only([
            'show',
            'sendCommand',
            'takeScreenshot',
            'blockWebsites',
        ]);
    }
}
```

### Step 2: Check Permissions in Controller Methods

```php
public function show(Room $room): JsonResponse
{
    $user = auth()->user();

    // Role-based permission check
    if (!$user->can('view-rooms')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Room access is already validated by middleware

    return response()->json(['room' => $room]);
}
```

### Step 3: Register Middleware

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'room.access' => EnsureUserHasRoomAccess::class,
    ]);
})
```

## Usage Examples

### Assign Teacher to Room

```php
$action = new AssignTeacherToRoomAction();
$teacher = User::find($teacherId);
$room = Room::find($roomId);

// Permanent assignment
$assignment = $action->handle($teacher, $room);

// Temporary assignment (expires in 30 days)
$assignment = $action->handle($teacher, $room, now()->addDays(30));
```

### Check Room Access

```php
$teacher = User::find($teacherId);
$roomId = 'room-uuid';

if ($teacher->hasAccessToRoom($roomId)) {
    // Teacher can access this room
} else {
    // Access denied
}
```

### Get User's Assigned Rooms

```php
$teacher = User::find($teacherId);

// Get all active room assignments
$rooms = $teacher->activeAssignedRooms;

// Get assignment details
$assignments = $teacher->activeRoomAssignments;
```

## Permission Matrix

| User Role  | Global Access | Room Access   | Notes                  |
| ---------- | ------------- | ------------- | ---------------------- |
| SuperAdmin | All rooms     | All rooms     | Bypass room checks     |
| Teacher    | None          | Assigned only | Via UserRoomAssignment |
| Student    | None          | None          | No room management     |

## Security Features

1. **UUID-based IDs** - Prevents ID enumeration attacks
2. **Expiration dates** - Automatic access revocation
3. **Active flags** - Manual access control
4. **Middleware protection** - Route-level security
5. **Role validation** - Prevents unauthorized assignments

## Best Practices

1. **Always use middleware** for room-specific routes
2. **Check both role and room permissions** in controllers
3. **Use transactions** when creating/updating assignments
4. **Log access attempts** for audit trails
5. **Validate expiration dates** before granting access

## Testing

```php
// Test room access
$teacher = User::factory()->create();
$room = Room::factory()->create();

// Assign teacher to room
$assignment = UserRoomAssignment::create([
    'user_id' => $teacher->id,
    'room_id' => $room->id,
    'is_active' => true,
]);

// Test access
$this->assertTrue($teacher->hasAccessToRoom($room->id));

// Test middleware
$response = $this->actingAs($teacher)
    ->get("/rooms/{$room->id}");

$response->assertStatus(200);
```

## Migration Guide

If you're adding this to an existing system:

1. Run the new migrations
2. Update your User model with the new relationships
3. Add the middleware to your routes
4. Update controllers to use the new permission checks
5. Seed initial room assignments for existing teachers

## API Routes Example

```php
// routes/api.php
Route::middleware(['auth'])->group(function () {
    // Room listing (filtered by user access)
    Route::get('/rooms', [RoomController::class, 'index']);

    // Room-specific routes (protected by room.access middleware)
    Route::middleware(['room.access'])->group(function () {
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::post('/rooms/{room}/commands', [RoomController::class, 'sendCommand']);
        Route::post('/rooms/{room}/screenshot', [RoomController::class, 'takeScreenshot']);
        Route::post('/rooms/{room}/block-websites', [RoomController::class, 'blockWebsites']);
    });

    // Admin-only routes
    Route::middleware(['permission:manage-user-rooms'])->group(function () {
        Route::post('/assign-teacher', [RoomController::class, 'assignTeacher']);
    });
});
```

This implementation provides a robust, secure, and scalable room-based access control system for your computer lab management application.

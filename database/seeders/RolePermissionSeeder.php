<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\UserRoomAssignment;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions using enums
        foreach (PermissionsEnum::cases() as $permission) {
            Permission::create(['name' => $permission->value]);
        }

        // update cache to know about the newly created permissions (required if using WithoutModelEvents in seeders)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles using enums as per Spatie documentation
        $superAdminRole = Role::create(['name' => RolesEnum::SUPERADMIN->value]);
        $teacherRole = Role::create(['name' => RolesEnum::TEACHER->value]);

        // Assign ALL permissions to Super Admin (global access)
        $superAdminRole->givePermissionTo(PermissionsEnum::cases());

        // Teacher permissions (room-specific classroom teaching)
        // These permissions will be granted per-room basis when assigning teachers to rooms
        $teacherRole->givePermissionTo([
            PermissionsEnum::VIEW_ROOMS,
            PermissionsEnum::VIEW_COMPUTER_STATUS,
            PermissionsEnum::SEND_LOCK_COMMAND,
            PermissionsEnum::SEND_RESTART_COMMAND,
            PermissionsEnum::SEND_MESSAGE_COMMAND,
            PermissionsEnum::TAKE_SCREENSHOT,
            PermissionsEnum::BLOCK_WEBSITES,
            PermissionsEnum::VIEW_COMMAND_HISTORY,
        ]);

        // Create sample rooms and demo teacher-room assignments
        $this->createSampleRoomsAndAssignments();
    }

    /**
     * Create sample rooms and demonstrate teacher-room assignments
     */
    private function createSampleRoomsAndAssignments(): void
    {
        // Import Room model
        $roomModel = Room::class;
        $userModel = User::class;

        // Create sample rooms
        $room1 = $roomModel::create([
            'name' => 'Computer Lab 1',
            'grid_rows' => 5,
            'grid_cols' => 6,
        ]);

        $room2 = $roomModel::create([
            'name' => 'Computer Lab 2',
            'grid_rows' => 4,
            'grid_cols' => 8,
        ]);

        // Create a demo teacher user
        $teacherUser = $userModel::create([
            'name' => 'John Teacher',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Assign teacher role
        $teacherUser->assignRole(RolesEnum::TEACHER->value);

        // Create a super admin user
        $adminUser = $userModel::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Assign super admin role
        $adminUser->assignRole(RolesEnum::SUPERADMIN->value);

        // Create room assignments for the teacher using our pivot table
        UserRoomAssignment::create([
            'user_id' => $teacherUser->id,
            'room_id' => $room1->id,
            'is_active' => true,
            'assigned_at' => now(),
            'expires_at' => null, // No expiration
        ]);

        // Assign teacher to second room with expiration (for testing)
        UserRoomAssignment::create([
            'user_id' => $teacherUser->id,
            'room_id' => $room2->id,
            'is_active' => true,
            'assigned_at' => now(),
            'expires_at' => now()->addDays(30), // Expires in 30 days
        ]);

        echo "✅ Teacher assigned to rooms with room-specific access control\n";
        echo "   - Teacher has access to Computer Lab 1 (permanent)\n";
        echo "   - Teacher has access to Computer Lab 2 (expires in 30 days)\n";
        echo "   - SuperAdmin has access to ALL rooms automatically\n";
    }
}

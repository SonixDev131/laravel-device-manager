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

        // Create all permissions using enums (use firstOrCreate to avoid duplicates)
        foreach (PermissionsEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }

        // update cache to know about the newly created permissions (required if using WithoutModelEvents in seeders)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles using enums as per Spatie documentation (use firstOrCreate to avoid duplicates)
        $superAdminRole = Role::firstOrCreate(['name' => RolesEnum::SUPERADMIN->value]);
        $teacherRole = Role::firstOrCreate(['name' => RolesEnum::TEACHER->value]);

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

        // Create sample rooms (use firstOrCreate to avoid duplicates)
        $room1 = $roomModel::firstOrCreate(
            ['name' => 'Computer Lab 1'],
            [
                'grid_rows' => 5,
                'grid_cols' => 6,
            ]
        );

        $room2 = $roomModel::firstOrCreate(
            ['name' => 'Computer Lab 2'],
            [
                'grid_rows' => 4,
                'grid_cols' => 8,
            ]
        );

        // Create a demo teacher user (use firstOrCreate to avoid duplicates)
        $teacherUser = $userModel::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'John Teacher',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign teacher role (sync to ensure clean role assignment)
        $teacherUser->syncRoles([RolesEnum::TEACHER->value]);

        // Create a super admin user (use firstOrCreate to avoid duplicates)
        $adminUser = $userModel::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign super admin role (sync to ensure clean role assignment)
        $adminUser->syncRoles([RolesEnum::SUPERADMIN->value]);

        // Create room assignments for the teacher using our pivot table (use firstOrCreate to avoid duplicates)
        UserRoomAssignment::firstOrCreate(
            [
                'user_id' => $teacherUser->id,
                'room_id' => $room1->id,
            ],
            [
                'is_active' => true,
                'assigned_at' => now(),
                'expires_at' => null, // No expiration
            ]
        );

        // Assign teacher to second room with expiration (for testing)
        UserRoomAssignment::firstOrCreate(
            [
                'user_id' => $teacherUser->id,
                'room_id' => $room2->id,
            ],
            [
                'is_active' => true,
                'assigned_at' => now(),
                'expires_at' => now()->addDays(30), // Expires in 30 days
            ]
        );

        echo "✅ Teacher assigned to rooms with room-specific access control\n";
        echo "   - Teacher has access to Computer Lab 1 (permanent)\n";
        echo "   - Teacher has access to Computer Lab 2 (expires in 30 days)\n";
        echo "   - SuperAdmin has access to ALL rooms automatically\n";
    }
}

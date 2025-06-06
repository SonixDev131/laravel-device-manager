<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles using enums
        $superAdminRole = app(Role::class)->findOrCreate(RolesEnum::SUPERADMIN->value, 'web');
        $teacherRole = app(Role::class)->findOrCreate(RolesEnum::TEACHER->value, 'web');

        // Create permissions
        $permissions = [
            'manage-rooms',
            'manage-computers',
            'send-commands',
            'view-rooms',
            'manage-agents',
            'system-admin',
        ];

        foreach ($permissions as $permission) {
            app(Permission::class)->findOrCreate($permission, 'web');
        }

        // Assign permissions to roles
        $superAdminRole->givePermissionTo($permissions);
        $teacherRole->givePermissionTo(['view-rooms', 'send-commands']);

        // User::factory(10)->create();

        // Create default super admin user
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);
        $superAdmin->assignRole(RolesEnum::SUPERADMIN);

        Room::factory()->count(10);
    }
}

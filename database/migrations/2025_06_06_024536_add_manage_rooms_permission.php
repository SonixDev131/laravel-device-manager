<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the permission
        $permission = Permission::create([
            'name' => PermissionsEnum::MANAGE_ROOMS->value,
            'guard_name' => 'web',
        ]);

        // Assign to SuperAdmin role
        $superAdminRole = Role::where('name', RolesEnum::SUPERADMIN->value)->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', PermissionsEnum::MANAGE_ROOMS->value)->delete();
    }
};

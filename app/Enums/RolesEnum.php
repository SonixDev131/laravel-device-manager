<?php

declare(strict_types=1);

namespace App\Enums;

enum RolesEnum: string
{
    // case NAMEINAPP = 'name-in-database';

    case SUPERADMIN = 'super-admin';
    case TEACHER = 'teacher';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Admin',
            self::TEACHER => 'Teacher',
        };
    }
}

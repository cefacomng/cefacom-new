<?php

namespace App\Enums;

enum RoleKey: string
{
    case SuperAdmin = 'super_admin';
    case AdminStaff = 'admin_staff';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminStaff => 'Admin Staff',
        };
    }
}

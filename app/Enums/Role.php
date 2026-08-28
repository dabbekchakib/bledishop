<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * Roles allowed to access the admin panel.
     *
     * @return array<int, string>
     */
    public static function adminPanelRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::Admin->value,
            self::Manager->value,
            self::Staff->value,
        ];
    }
}

<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Accountant => 'Contador',
            self::Viewer => 'Consulta',
        };
    }

    public function canAccessPanel(): bool
    {
        return in_array($this, [self::Admin, self::Accountant], true);
    }
}

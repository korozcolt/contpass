<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case Admin = 'admin';
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return $this->label();
    }

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

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Accountant => 'success',
            self::Viewer => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Admin => Heroicon::ShieldCheck,
            self::Accountant => Heroicon::Calculator,
            self::Viewer => Heroicon::Eye,
        };
    }
}

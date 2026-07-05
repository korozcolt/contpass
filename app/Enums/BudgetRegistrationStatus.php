<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum BudgetRegistrationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case FullyObligated = 'fully_obligated';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::FullyObligated => 'Obligado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::FullyObligated => 'warning',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Active => Heroicon::CheckCircle,
            self::FullyObligated => Heroicon::LockClosed,
            self::Cancelled => Heroicon::XCircle,
        };
    }
}

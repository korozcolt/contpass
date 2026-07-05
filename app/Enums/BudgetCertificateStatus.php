<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum BudgetCertificateStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case FullyCommitted = 'fully_committed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::FullyCommitted => 'Comprometido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::FullyCommitted => 'warning',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Active => Heroicon::CheckCircle,
            self::FullyCommitted => Heroicon::LockClosed,
            self::Cancelled => Heroicon::XCircle,
        };
    }
}

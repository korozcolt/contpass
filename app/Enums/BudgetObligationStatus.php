<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum BudgetObligationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Approved => 'Aprobada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Approved => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::PencilSquare,
            self::Approved => Heroicon::CheckBadge,
            self::Cancelled => Heroicon::XCircle,
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum VoucherStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Void = 'void';
    case Adjusted = 'adjusted';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Approved => 'Aprobado',
            self::Void => 'Anulado',
            self::Adjusted => 'Ajustado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Approved => 'success',
            self::Void => 'danger',
            self::Adjusted => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::PencilSquare,
            self::Approved => Heroicon::CheckCircle,
            self::Void => Heroicon::XCircle,
            self::Adjusted => Heroicon::AdjustmentsHorizontal,
        };
    }
}

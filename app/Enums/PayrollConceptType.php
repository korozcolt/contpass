<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum PayrollConceptType: string implements HasColor, HasIcon, HasLabel
{
    case Earning = 'earning';
    case Deduction = 'deduction';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Earning => 'Devengado',
            self::Deduction => 'Descuento',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Earning => 'success',
            self::Deduction => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Earning => Heroicon::ArrowTrendingUp,
            self::Deduction => Heroicon::ArrowTrendingDown,
        };
    }
}

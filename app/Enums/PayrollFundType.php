<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum PayrollFundType: string implements HasColor, HasIcon, HasLabel
{
    case Pension = 'pension';
    case Health = 'health';
    case Severance = 'severance';
    case Arl = 'arl';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Pension => 'Pensión',
            self::Health => 'Salud',
            self::Severance => 'Cesantías',
            self::Arl => 'ARL',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pension => 'primary',
            self::Health => 'success',
            self::Severance => 'warning',
            self::Arl => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pension => Heroicon::BuildingLibrary,
            self::Health => Heroicon::Heart,
            self::Severance => Heroicon::Banknotes,
            self::Arl => Heroicon::ShieldCheck,
        };
    }
}

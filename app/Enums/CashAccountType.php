<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CashAccountType: string implements HasColor, HasIcon, HasLabel
{
    case Bank = 'bank';
    case Cash = 'cash';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Banco',
            self::Cash => 'Caja',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Bank => 'primary',
            self::Cash => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Bank => Heroicon::BuildingLibrary,
            self::Cash => Heroicon::Banknotes,
        };
    }
}

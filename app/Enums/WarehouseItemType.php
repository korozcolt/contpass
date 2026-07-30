<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum WarehouseItemType: string implements HasColor, HasIcon, HasLabel
{
    case Consumable = 'consumable';
    case Returnable = 'returnable';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Consumable => 'Consumo',
            self::Returnable => 'Devolutivo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Consumable => 'info',
            self::Returnable => 'primary',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Consumable => Heroicon::Beaker,
            self::Returnable => Heroicon::ComputerDesktop,
        };
    }
}

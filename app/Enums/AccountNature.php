<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AccountNature: string implements HasColor, HasIcon, HasLabel
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'Débito',
            self::Credit => 'Crédito',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Debit => 'info',
            self::Credit => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Debit => Heroicon::ArrowDownLeft,
            self::Credit => Heroicon::ArrowUpRight,
        };
    }
}

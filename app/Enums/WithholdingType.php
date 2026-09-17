<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum WithholdingType: string implements HasColor, HasIcon, HasLabel
{
    case ReteFuente = 'rete_fuente';
    case ReteIVA = 'rete_iva';
    case Ica = 'ica';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReteFuente => 'Retención en la fuente',
            self::ReteIVA => 'Retención de IVA',
            self::Ica => 'ICA',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ReteFuente => 'info',
            self::ReteIVA => 'gray',
            self::Ica => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::ReteFuente => Heroicon::ReceiptPercent,
            self::ReteIVA => Heroicon::CurrencyDollar,
            self::Ica => Heroicon::MapPin,
        };
    }
}

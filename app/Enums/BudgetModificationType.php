<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum BudgetModificationType: string implements HasColor, HasIcon, HasLabel
{
    case Addition = 'addition';
    case Reduction = 'reduction';
    case Transfer = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Addition => 'Adición',
            self::Reduction => 'Reducción',
            self::Transfer => 'Traslado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Addition => 'success',
            self::Reduction => 'danger',
            self::Transfer => 'warning',
        };
    }

    public function getIcon(): string|Heroicon|null
    {
        return match ($this) {
            self::Addition => Heroicon::PlusCircle,
            self::Reduction => Heroicon::MinusCircle,
            self::Transfer => Heroicon::ArrowsRightLeft,
        };
    }
}

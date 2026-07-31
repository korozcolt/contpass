<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum PettyCashMovementType: string implements HasColor, HasIcon, HasLabel
{
    case Opening = 'opening';
    case Expense = 'expense';
    case Replenishment = 'replenishment';
    case Closure = 'closure';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Apertura',
            self::Expense => 'Gasto',
            self::Replenishment => 'Reembolso',
            self::Closure => 'Cierre',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Opening => 'info',
            self::Expense => 'danger',
            self::Replenishment => 'success',
            self::Closure => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Opening => Heroicon::PlusCircle,
            self::Expense => Heroicon::ArrowTrendingDown,
            self::Replenishment => Heroicon::ArrowPath,
            self::Closure => Heroicon::XCircle,
        };
    }

    public function increasesBalance(): bool
    {
        return in_array($this, [self::Opening, self::Replenishment], true);
    }

    public function decreasesBalance(): bool
    {
        return in_array($this, [self::Expense, self::Closure], true);
    }
}

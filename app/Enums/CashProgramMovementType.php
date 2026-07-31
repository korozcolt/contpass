<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CashProgramMovementType: string implements HasColor, HasIcon, HasLabel
{
    case Income = 'income';
    case Expense = 'expense';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Gasto',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Income => 'success',
            self::Expense => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Income => Heroicon::ArrowTrendingUp,
            self::Expense => Heroicon::ArrowTrendingDown,
        };
    }

    public function isIncome(): bool
    {
        return $this === self::Income;
    }

    public function isExpense(): bool
    {
        return $this === self::Expense;
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum VoucherType: string implements HasColor, HasIcon, HasLabel
{
    case Income = 'income';
    case Expense = 'expense';
    case Payment = 'payment';
    case Adjustment = 'adjustment';
    case Budget = 'budget';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Egreso',
            self::Payment => 'Pago',
            self::Adjustment => 'Ajuste',
            self::Budget => 'Presupuestal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Income => 'success',
            self::Expense => 'danger',
            self::Payment => 'primary',
            self::Adjustment => 'warning',
            self::Budget => 'info',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Income => Heroicon::ArrowTrendingUp,
            self::Expense => Heroicon::ArrowTrendingDown,
            self::Payment => Heroicon::Banknotes,
            self::Adjustment => Heroicon::AdjustmentsHorizontal,
            self::Budget => Heroicon::ChartBar,
        };
    }
}

<?php

namespace App\Enums;

enum VoucherType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Payment = 'payment';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Egreso',
            self::Payment => 'Pago',
            self::Adjustment => 'Ajuste',
        };
    }
}

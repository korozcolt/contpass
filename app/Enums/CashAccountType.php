<?php

namespace App\Enums;

enum CashAccountType: string
{
    case Bank = 'bank';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Banco',
            self::Cash => 'Caja',
        };
    }
}

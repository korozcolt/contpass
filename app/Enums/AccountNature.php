<?php

namespace App\Enums;

enum AccountNature: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'Débito',
            self::Credit => 'Crédito',
        };
    }
}

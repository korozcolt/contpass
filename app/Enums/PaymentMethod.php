<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Check = 'check';
    case Card = 'card';
    case Deposit = 'deposit';
    case Cash = 'cash';
    case OtherAuthorized = 'other_authorized';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transferencia',
            self::Check => 'Cheque',
            self::Card => 'Tarjeta',
            self::Deposit => 'Depósito',
            self::Cash => 'Efectivo',
            self::OtherAuthorized => 'Otro autorizado',
        };
    }

    public function isBancarized(): bool
    {
        return $this !== self::Cash;
    }
}

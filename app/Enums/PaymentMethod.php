<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case BankTransfer = 'bank_transfer';
    case Check = 'check';
    case Card = 'card';
    case Deposit = 'deposit';
    case Cash = 'cash';
    case OtherAuthorized = 'other_authorized';

    public function getLabel(): string
    {
        return $this->label();
    }

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

    public function getColor(): string
    {
        return match ($this) {
            self::BankTransfer, self::Deposit => 'primary',
            self::Check => 'info',
            self::Card => 'success',
            self::Cash => 'danger',
            self::OtherAuthorized => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::BankTransfer => Heroicon::ArrowsRightLeft,
            self::Check => Heroicon::DocumentCheck,
            self::Card => Heroicon::CreditCard,
            self::Deposit => Heroicon::BuildingLibrary,
            self::Cash => Heroicon::Banknotes,
            self::OtherAuthorized => Heroicon::EllipsisHorizontalCircle,
        };
    }
}

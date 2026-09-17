<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BankStatementLineStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Matched => 'Coincidencia',
            self::Rejected => 'Rechazada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Matched => 'success',
            self::Rejected => 'danger',
        };
    }
}

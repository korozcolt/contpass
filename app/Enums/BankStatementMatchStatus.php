<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BankStatementMatchStatus: string implements HasLabel
{
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Discarded = 'discarded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Proposed => 'Propuesta',
            self::Confirmed => 'Confirmada',
            self::Discarded => 'Descartada',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BankStatementMatchConfidence: string implements HasColor, HasLabel
{
    case High = 'high';
    case Candidate = 'candidate';

    public function getLabel(): string
    {
        return match ($this) {
            self::High => 'Alta confianza',
            self::Candidate => 'Candidato',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::High => 'success',
            self::Candidate => 'gray',
        };
    }
}

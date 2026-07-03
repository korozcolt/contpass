<?php

namespace App\Enums;

enum ThirdPartyType: string
{
    case NaturalPerson = 'natural_person';
    case LegalEntity = 'legal_entity';

    public function label(): string
    {
        return match ($this) {
            self::NaturalPerson => 'Persona natural',
            self::LegalEntity => 'Persona jurídica',
        };
    }
}

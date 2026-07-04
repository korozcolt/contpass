<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ThirdPartyType: string implements HasColor, HasIcon, HasLabel
{
    case NaturalPerson = 'natural_person';
    case LegalEntity = 'legal_entity';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::NaturalPerson => 'Persona natural',
            self::LegalEntity => 'Persona jurídica',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NaturalPerson => 'info',
            self::LegalEntity => 'primary',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::NaturalPerson => Heroicon::Identification,
            self::LegalEntity => Heroicon::BuildingOffice2,
        };
    }
}

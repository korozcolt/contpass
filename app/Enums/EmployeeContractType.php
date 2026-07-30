<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EmployeeContractType: string implements HasColor, HasIcon, HasLabel
{
    case Indefinite = 'indefinite';
    case FixedTerm = 'fixed_term';
    case ServiceProvision = 'service_provision';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Indefinite => 'Indefinido',
            self::FixedTerm => 'Término fijo',
            self::ServiceProvision => 'Prestación de servicios',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Indefinite => 'success',
            self::FixedTerm => 'warning',
            self::ServiceProvision => 'info',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Indefinite => Heroicon::DocumentCheck,
            self::FixedTerm => Heroicon::CalendarDays,
            self::ServiceProvision => Heroicon::Briefcase,
        };
    }
}

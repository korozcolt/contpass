<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SignatoryArea: string implements HasColor, HasIcon, HasLabel
{
    case Budget = 'budget';
    case Accounting = 'accounting';
    case Treasury = 'treasury';
    case InternalControl = 'internal_control';
    case LegalRepresentative = 'legal_representative';
    case GeneralSecretary = 'general_secretary';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Budget => 'Presupuesto',
            self::Accounting => 'Contabilidad',
            self::Treasury => 'Tesorería',
            self::InternalControl => 'Control Interno',
            self::LegalRepresentative => 'Representante Legal',
            self::GeneralSecretary => 'Secretaría General',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Budget => 'info',
            self::Accounting => 'success',
            self::Treasury => 'warning',
            self::InternalControl => 'danger',
            self::LegalRepresentative => 'primary',
            self::GeneralSecretary => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Budget => Heroicon::ChartBar,
            self::Accounting => Heroicon::BookOpen,
            self::Treasury => Heroicon::Banknotes,
            self::InternalControl => Heroicon::ShieldCheck,
            self::LegalRepresentative => Heroicon::Identification,
            self::GeneralSecretary => Heroicon::BuildingOffice2,
        };
    }
}

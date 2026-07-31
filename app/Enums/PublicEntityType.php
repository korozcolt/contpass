<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PublicEntityType: string implements HasLabel
{
    case Municipality = 'municipality';
    case PublicEstablishment = 'public_establishment';
    case Ese = 'ese';
    case Esp = 'esp';
    case Ips = 'ips';

    public function getLabel(): string
    {
        return match ($this) {
            self::Municipality => 'Municipio',
            self::PublicEstablishment => 'Establecimiento Público',
            self::Ese => 'Empresa Social del Estado (ESE)',
            self::Esp => 'Empresa de Servicios Públicos (ESP)',
            self::Ips => 'Institución Prestadora de Salud (IPS)',
        };
    }
}

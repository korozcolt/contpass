<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Formato provisional (03-RESEARCH.md Open Question #1, sin muestra real de extracto).
 * Ajustar columnAliases()/dateFormats() por perfil cuando el usuario provea un archivo
 * real de Bancolombia/Davivienda, sin tocar el motor de encoding/delimitador de
 * ImportBankStatement.
 */
enum BankProfile: string implements HasLabel
{
    case Bancolombia = 'bancolombia';
    case Davivienda = 'davivienda';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bancolombia => 'Bancolombia',
            self::Davivienda => 'Davivienda',
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function columnAliases(): array
    {
        return match ($this) {
            self::Bancolombia => [
                'date' => ['Fecha'],
                'description' => ['Descripción', 'Descripcion'],
                'amount' => ['Valor'],
                'reference' => ['Referencia', 'Documento'],
            ],
            self::Davivienda => [
                'date' => ['Fecha'],
                'description' => ['Concepto'],
                'amount' => ['Valor', 'Monto'],
                'reference' => ['Documento', 'Referencia'],
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    public function dateFormats(): array
    {
        return ['d/m/Y', 'Y-m-d', 'd-m-Y'];
    }

    /**
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        return ['date', 'description', 'amount'];
    }
}

<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Void = 'void';
    case Adjusted = 'adjusted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Approved => 'Aprobado',
            self::Void => 'Anulado',
            self::Adjusted => 'Ajustado',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum WarehouseMovementType: string implements HasColor, HasIcon, HasLabel
{
    case Entry = 'entry';
    case Exit = 'exit';
    case Transfer = 'transfer';
    case Writeoff = 'writeoff';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada',
            self::Exit => 'Salida',
            self::Transfer => 'Traslado',
            self::Writeoff => 'Baja',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Entry => 'success',
            self::Exit => 'danger',
            self::Transfer => 'info',
            self::Writeoff => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Entry => Heroicon::ArrowLeftCircle,
            self::Exit => Heroicon::ArrowRightCircle,
            self::Transfer => Heroicon::ArrowsRightLeft,
            self::Writeoff => Heroicon::Trash,
        };
    }

    public function decreasesStock(): bool
    {
        return in_array($this, [self::Exit, self::Writeoff], true);
    }

    public function increasesStock(): bool
    {
        return $this === self::Entry;
    }
}

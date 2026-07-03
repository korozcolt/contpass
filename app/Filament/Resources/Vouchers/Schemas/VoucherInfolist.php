<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VoucherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comprobante aprobado')->schema([
                TextEntry::make('number')->label('Número'),
                TextEntry::make('date')->label('Fecha')->date(),
                TextEntry::make('type')->label('Tipo')->badge(),
                TextEntry::make('status')->label('Estado')->badge(),
                TextEntry::make('thirdParty.name')->label('Tercero')->placeholder('-'),
                TextEntry::make('description')->label('Descripción'),
                TextEntry::make('approved_at')->label('Aprobado en')->dateTime()->placeholder('-'),
            ])->columns(3),
        ]);
    }
}

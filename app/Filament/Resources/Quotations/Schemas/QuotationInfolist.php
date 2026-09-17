<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Filament\Resources\Vouchers\VoucherResource;
use App\Models\Quotation;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cotización')->schema([
                TextEntry::make('number')->label('Número'),
                TextEntry::make('status')->label('Estado')->badge()
                    ->state(fn (Quotation $record) => $record->effectiveStatus()),
                TextEntry::make('thirdParty.name')->label('Tercero'),
                TextEntry::make('expires_on')->label('Vigencia hasta')->date(),
                TextEntry::make('total')->label('Total')->money('COP'),
                TextEntry::make('rejection_reason')->label('Motivo de rechazo')->placeholder('-'),
                TextEntry::make('voucher.number')
                    ->label('Comprobante generado')
                    ->placeholder('Aún no convertida')
                    ->url(fn (Quotation $record): ?string => $record->voucher_id
                        ? VoucherResource::getUrl('view', ['record' => $record->voucher_id])
                        : null),
            ])->columns(3),
            Section::make('Líneas')->schema([
                RepeatableEntry::make('lines')->label('')->schema([
                    TextEntry::make('description')->label('Descripción'),
                    TextEntry::make('quantity')->label('Cantidad'),
                    TextEntry::make('unit_price')->label('Valor unitario')->money('COP'),
                    TextEntry::make('subtotal')->label('Subtotal')->money('COP'),
                ])->columns(4),
            ]),
        ]);
    }
}

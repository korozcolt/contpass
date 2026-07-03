<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('paid_on', 'desc')
            ->columns([
                TextColumn::make('paid_on')->label('Fecha')->date()->sortable(),
                TextColumn::make('voucher.number')->label('Comprobante')->searchable(),
                TextColumn::make('sourceVoucher.number')->label('Origen')->placeholder('-')->searchable(),
                TextColumn::make('cashAccount.name')->label('Caja/Banco')->searchable(),
                TextColumn::make('method')->label('Medio')->badge(),
                TextColumn::make('amount')->label('Valor')->money('COP')->sortable(),
                IconColumn::make('is_bancarized')->label('Bancarizado')->boolean(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\IncomeRecords\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncomeRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('accrual_date', 'desc')
            ->columns([
                TextColumn::make('accrual_date')->label('Fecha')->date()->sortable(),
                TextColumn::make('voucher.number')->label('Comprobante')->searchable(),
                TextColumn::make('voucher.thirdParty.name')->label('Tercero')->searchable(),
                TextColumn::make('support_number')->label('Soporte')->searchable(),
                TextColumn::make('revenueAccount.full_name')->label('Ingreso')->toggleable(),
                TextColumn::make('amount')->label('Valor')->money('COP')->sortable(),
            ]);
    }
}

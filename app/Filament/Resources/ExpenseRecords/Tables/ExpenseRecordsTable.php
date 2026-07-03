<?php

namespace App\Filament\Resources\ExpenseRecords\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseRecordsTable
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
                TextColumn::make('amount')->label('Valor')->money('COP')->sortable(),
                TextColumn::make('withholding_amount')->label('Retención')->money('COP')->sortable(),
                IconColumn::make('has_valid_support')->label('Soporte')->boolean(),
                IconColumn::make('is_deductible')->label('Deducible')->boolean(),
            ]);
    }
}

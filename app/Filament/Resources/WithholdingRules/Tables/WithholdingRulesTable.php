<?php

namespace App\Filament\Resources\WithholdingRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WithholdingRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('concept')->label('Concepto')->searchable()->sortable(),
                TextColumn::make('minimum_base')->label('Base')->money('COP')->sortable(),
                TextColumn::make('rate')->label('Tarifa')->suffix('%')->sortable(),
                TextColumn::make('chartAccount.full_name')->label('Cuenta')->searchable(),
                TextColumn::make('starts_on')->label('Desde')->date()->sortable(),
                TextColumn::make('ends_on')->label('Hasta')->date()->placeholder('Abierta'),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

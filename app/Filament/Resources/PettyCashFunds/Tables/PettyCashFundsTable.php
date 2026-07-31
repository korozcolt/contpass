<?php

namespace App\Filament\Resources\PettyCashFunds\Tables;

use App\Models\PettyCashFund;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PettyCashFundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Fondo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Custodio')
                    ->placeholder('—'),
                TextColumn::make('authorized_amount')
                    ->label('Base autorizada')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('available_balance')
                    ->label('Saldo disponible')
                    ->money('COP')
                    ->color(fn (PettyCashFund $record): string => $record->available_balance >= 0 ? 'success' : 'danger'),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

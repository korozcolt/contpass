<?php

namespace App\Filament\Resources\AccountingPeriods\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountingPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_on')->label('Inicio')->date()->sortable(),
                TextColumn::make('ends_on')->label('Fin')->date()->sortable(),
                IconColumn::make('is_closed')->label('Cerrado')->boolean(),
                TextColumn::make('closed_at')->label('Cerrado en')->dateTime()->placeholder('-'),
            ])
            ->recordActions([
                Action::make('close')
                    ->label('Cerrar')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => ! $record->is_closed)
                    ->action(fn ($record) => $record->update(['is_closed' => true, 'closed_at' => now()])),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

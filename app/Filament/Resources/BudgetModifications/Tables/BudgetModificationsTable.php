<?php

namespace App\Filament\Resources\BudgetModifications\Tables;

use App\Enums\BudgetModificationType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BudgetModificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('effective_date', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('document_reference')
                    ->label('Acto administrativo')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('destinationAppropriation.name')
                    ->label('Rubro destino')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('sourceAppropriation.code')
                    ->label('Rubro origen')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('effective_date')
                    ->label('Vigencia')
                    ->date()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Registró')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(BudgetModificationType::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\CashProgramItems\Tables;

use App\Enums\CashProgramMovementType;
use App\Filament\Resources\CashProgramItems\Schemas\CashProgramItemForm;
use App\Models\CashProgramItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashProgramItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('month')
            ->columns([
                TextColumn::make('fiscal_year')
                    ->label('Vigencia')
                    ->sortable(),
                TextColumn::make('movement_type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('month')
                    ->label('Mes')
                    ->formatStateUsing(fn (int $state): string => CashProgramItemForm::months()[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('rubro')
                    ->label('Rubro')
                    ->state(fn (CashProgramItem $record): string => $record->movement_type === CashProgramMovementType::Expense
                        ? ($record->budgetAppropriation?->name ?? '—')
                        : ($record->budgetRevenue?->name ?? '—')),
                TextColumn::make('projected_amount')
                    ->label('Proyectado')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('executed_amount')
                    ->label('Ejecutado')
                    ->money('COP')
                    ->color(fn (CashProgramItem $record): string => $record->executed_amount >= (float) $record->projected_amount ? 'success' : 'warning'),
                TextColumn::make('deviation')
                    ->label('Desviación')
                    ->money('COP')
                    ->color(fn (CashProgramItem $record): string => $record->deviation <= 0 ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->label('Vigencia')
                    ->options(fn (): array => CashProgramItem::query()
                        ->distinct()
                        ->orderByDesc('fiscal_year')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->all()),
                SelectFilter::make('movement_type')
                    ->label('Tipo')
                    ->options(CashProgramMovementType::class),
                SelectFilter::make('month')
                    ->label('Mes')
                    ->options(CashProgramItemForm::months()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

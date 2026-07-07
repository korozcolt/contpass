<?php

namespace App\Filament\Resources\BudgetRevenues\Tables;

use App\Models\BudgetRevenue;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BudgetRevenuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fiscal_year', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Rubro')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'corriente' => 'Ingresos Corrientes',
                        'capital' => 'Recursos de Capital',
                        'fondos_especiales' => 'Fondos Especiales',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'capital' => 'warning',
                        'fondos_especiales' => 'info',
                        default => 'success',
                    }),
                TextColumn::make('projected_amount')
                    ->label('Meta proyectada')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('executed_amount')
                    ->label('Recaudado')
                    ->money('COP')
                    ->color(fn (BudgetRevenue $record): string => $record->executed_amount >= (float) $record->projected_amount ? 'success' : 'warning'),
                ProgressColumn::make('execution_percentage')
                    ->label('% Ejecución')
                    ->color(fn (BudgetRevenue $record): string => match (true) {
                        $record->execution_percentage >= 90 => 'success',
                        $record->execution_percentage >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('fiscal_year')
                    ->label('Vigencia')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->label('Vigencia')
                    ->options(fn (): array => BudgetRevenue::query()
                        ->distinct()
                        ->orderByDesc('fiscal_year')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->all()),
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'corriente' => 'Ingresos Corrientes',
                        'capital' => 'Recursos de Capital',
                        'fondos_especiales' => 'Fondos Especiales',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

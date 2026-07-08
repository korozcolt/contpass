<?php

namespace App\Filament\Resources\BudgetRevenues\Tables;

use App\Models\BudgetRevenue;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('execution_percentage')
                    ->label('% Ejecución')
                    ->html()
                    ->formatStateUsing(function (float $state, BudgetRevenue $record): string {
                        $percent = min(100, max(0, $state));
                        $colorClass = match (true) {
                            $state >= 90 => 'bg-emerald-500',
                            $state >= 50 => 'bg-amber-500',
                            default => 'bg-rose-500',
                        };

                        return "
                            <div class='flex items-center gap-2' style='min-width: 120px;'>
                                <div class='w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700'>
                                    <div class='{$colorClass} h-2 rounded-full transition-all' style='width: {$percent}%'></div>
                                </div>
                                <span class='text-xs font-semibold shrink-0'>".number_format($state, 1).'%</span>
                            </div>
                        ';
                    })
                    ->sortable(),
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

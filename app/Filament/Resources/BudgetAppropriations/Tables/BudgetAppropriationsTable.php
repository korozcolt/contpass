<?php

namespace App\Filament\Resources\BudgetAppropriations\Tables;

use App\Models\BudgetAppropriation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetAppropriationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fiscal_year', 'desc')
            ->columns([
                TextColumn::make('fiscal_year')->label('Año fiscal')->sortable(),
                TextColumn::make('code')->label('Código')->searchable()->sortable(),
                TextColumn::make('name')->label('Rubro')->searchable()->sortable(),
                TextColumn::make('total_appropriation')->label('Apropiación total')->money('COP')->sortable(),
                TextColumn::make('available_amount')->label('Disponible')->money('COP')
                    ->sortable()
                    ->color(fn (BudgetAppropriation $record): string => $record->available_amount <= 0 ? 'danger' : 'success'),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

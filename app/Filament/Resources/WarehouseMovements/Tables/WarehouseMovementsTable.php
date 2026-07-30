<?php

namespace App\Filament\Resources\WarehouseMovements\Tables;

use App\Enums\WarehouseMovementType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WarehouseMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                TextColumn::make('warehouse.name')->label('Almacén')->searchable(),
                TextColumn::make('destinationWarehouse.name')->label('Destino')->placeholder('—'),
                TextColumn::make('date')->label('Fecha')->date('Y-m-d')->sortable(),
                TextColumn::make('description')->label('Descripción')->limit(50),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(WarehouseMovementType::class),
            ])
            ->recordActions([EditAction::make()]);
    }
}

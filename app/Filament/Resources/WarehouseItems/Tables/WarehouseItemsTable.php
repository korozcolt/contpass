<?php

namespace App\Filament\Resources\WarehouseItems\Tables;

use App\Enums\WarehouseItemType;
use App\Models\WarehouseItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WarehouseItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Código')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('unit_of_measure')->label('UM')->toggleable(),
                TextColumn::make('current_stock')
                    ->label('Stock actual')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->color(fn (WarehouseItem $record): string => $record->minimum_stock !== null && $record->current_stock < (float) $record->minimum_stock ? 'danger' : 'success'),
                TextColumn::make('minimum_stock')->label('Stock mínimo')->numeric(decimalPlaces: 2)->alignEnd()->toggleable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(WarehouseItemType::class),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

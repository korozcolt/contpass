<?php

namespace App\Filament\Resources\CompanySignatories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanySignatoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('area')->label('Dependencia')->badge()->sortable(),
                TextColumn::make('full_name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('position')->label('Cargo')->toggleable(),
                TextColumn::make('identification')->label('Cédula')->toggleable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

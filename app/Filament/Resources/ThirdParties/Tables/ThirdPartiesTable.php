<?php

namespace App\Filament\Resources\ThirdParties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThirdPartiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tercero')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('tax_id')->label('NIT/Cédula')->searchable(),
                TextColumn::make('verification_digit')->label('DV')->alignCenter(),
                TextColumn::make('city')->label('Ciudad')->searchable(),
                TextColumn::make('email')->label('Correo')->searchable()->toggleable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

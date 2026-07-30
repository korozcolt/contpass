<?php

namespace App\Filament\Resources\PayrollConcepts\Tables;

use App\Enums\PayrollConceptType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollConceptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Código')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(PayrollConceptType::class),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

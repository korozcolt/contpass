<?php

namespace App\Filament\Resources\PayrollFunds\Tables;

use App\Enums\PayrollFundType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollFundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('nit')->label('NIT')->toggleable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(PayrollFundType::class),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

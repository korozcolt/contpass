<?php

namespace App\Filament\Resources\CashAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Caja/Banco')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('chartAccount.full_name')->label('Cuenta PUC')->searchable(),
                TextColumn::make('bank_name')->label('Banco')->toggleable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Enums\EmployeeContractType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('position')->label('Cargo')->searchable()->sortable(),
                TextColumn::make('dependency.name')->label('Dependencia')->toggleable(),
                TextColumn::make('contract_type')->label('Contrato')->badge()->toggleable(),
                TextColumn::make('base_salary')->label('Salario base')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('contract_type')->label('Contrato')->options(EmployeeContractType::class),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

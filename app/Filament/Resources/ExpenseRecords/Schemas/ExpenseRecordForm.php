<?php

namespace App\Filament\Resources\ExpenseRecords\Schemas;

use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ExpenseRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                AccountingFormFields::thirdParty(),
                AccountingFormFields::date('accrual_date', 'Fecha de causación'),
                AccountingFormFields::chartAccountPrefixes('expense_account_id', 'Cuenta gasto/costo clase 5 o 6', ['5', '6']),
                AccountingFormFields::chartAccount('payable_account_id', 'Cuenta por pagar clase 2', '2'),
                TextInput::make('support_type')->label('Tipo de soporte')->default('Factura/Cuenta de cobro')->required()->maxLength(120),
                TextInput::make('support_number')->label('Número de soporte')->prefix('#')->required()->maxLength(120),
                AccountingFormFields::money('amount', 'Valor del egreso'),
                TextInput::make('description')->label('Descripción'),
                Toggle::make('has_valid_support')->label('Soporte idóneo')->default(true),
                Toggle::make('is_deductible')->label('Deducible')->default(true),
            ]),
        ]);
    }
}

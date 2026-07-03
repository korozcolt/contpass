<?php

namespace App\Filament\Resources\IncomeRecords\Schemas;

use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class IncomeRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                AccountingFormFields::thirdParty(),
                AccountingFormFields::date('accrual_date', 'Fecha de causación'),
                AccountingFormFields::chartAccount('revenue_account_id', 'Cuenta de ingreso clase 4', '4'),
                AccountingFormFields::chartAccount('receivable_account_id', 'Cuenta por cobrar clase 13', '13'),
                TextInput::make('support_number')->label('Número de soporte')->prefix('#')->required()->maxLength(120),
                AccountingFormFields::money('amount', 'Valor del ingreso'),
                TextInput::make('description')->label('Descripción')->columnSpanFull(),
            ]),
        ]);
    }
}

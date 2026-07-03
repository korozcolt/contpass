<?php

namespace App\Filament\Resources\WithholdingRules\Schemas;

use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WithholdingRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('concept')->label('Concepto')->required()->maxLength(255),
                AccountingFormFields::chartAccount('chart_account_id', 'Cuenta de retención', '2365'),
                AccountingFormFields::money('minimum_base', 'Base mínima')->default(0),
                AccountingFormFields::percent('rate', 'Tarifa'),
                AccountingFormFields::date('starts_on', 'Inicio de vigencia'),
                AccountingFormFields::date('ends_on', 'Fin de vigencia')->required(false),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\PayrollFunds\Schemas;

use App\Enums\PayrollFundType;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PayrollFundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                Select::make('type')
                    ->label('Tipo')
                    ->options(PayrollFundType::class)
                    ->required()
                    ->native(false),
                TextInput::make('nit')->label('NIT')->maxLength(30),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]),
        ]);
    }
}

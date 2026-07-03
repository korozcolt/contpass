<?php

namespace App\Filament\Resources\CashAccounts\Schemas;

use App\Enums\CashAccountType;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CashAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                Select::make('type')
                    ->label('Tipo')
                    ->options(collect(CashAccountType::cases())->mapWithKeys(fn (CashAccountType $type) => [$type->value => $type->label()])->all())
                    ->required(),
                AccountingFormFields::chartAccount('chart_account_id', 'Cuenta PUC clase 11', '11'),
                TextInput::make('bank_name')->label('Banco')->prefix('Banco'),
                TextInput::make('account_number')->label('Número de cuenta')->prefix('#'),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }
}

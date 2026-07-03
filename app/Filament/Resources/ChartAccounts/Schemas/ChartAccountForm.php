<?php

namespace App\Filament\Resources\ChartAccounts\Schemas;

use App\Enums\AccountNature;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ChartAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('code')->label('Código PUC')->prefix('#')->required()->maxLength(20),
                Select::make('nature')
                    ->label('Naturaleza')
                    ->options(collect(AccountNature::cases())->mapWithKeys(fn (AccountNature $nature) => [$nature->value => $nature->label()])->all())
                    ->required(),
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }
}

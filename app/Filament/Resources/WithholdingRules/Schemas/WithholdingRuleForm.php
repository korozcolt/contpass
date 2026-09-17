<?php

namespace App\Filament\Resources\WithholdingRules\Schemas;

use App\Enums\WithholdingType;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WithholdingRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                Select::make('type')
                    ->label('Tipo de retención')
                    ->options(WithholdingType::class)
                    ->native(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (Set $set) => $set('municipality_id', null)),
                TextInput::make('description')->label('Descripción (opcional)')->maxLength(255),
                AccountingFormFields::municipality()
                    ->placeholder('Selecciona el municipio de la regla ICA')
                    ->visible(fn (Get $get): bool => $get('type') === WithholdingType::Ica)
                    ->required(fn (Get $get): bool => $get('type') === WithholdingType::Ica),
                AccountingFormFields::chartAccount('chart_account_id', 'Cuenta de retención', '2365'),
                AccountingFormFields::money('minimum_base', 'Base mínima')->default(0),
                AccountingFormFields::percent('rate', 'Tarifa')
                    ->helperText(fn (Get $get): ?string => $get('type') === WithholdingType::Ica
                        ? 'El ICA se expresa típicamente en por mil (‰). Ingresa el equivalente porcentual: ej. 9.66‰ = 0.966%.'
                        : null),
                AccountingFormFields::date('starts_on', 'Inicio de vigencia'),
                AccountingFormFields::date('ends_on', 'Fin de vigencia')->required(false),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }
}

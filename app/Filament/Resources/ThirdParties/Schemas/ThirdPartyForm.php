<?php

namespace App\Filament\Resources\ThirdParties\Schemas;

use App\Enums\ThirdPartyType;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ThirdPartyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Section::make('Identificación tributaria')->schema([
                Grid::make(3)->schema([
                    Select::make('type')
                        ->label('Tipo')
                        ->options(collect(ThirdPartyType::cases())->mapWithKeys(fn (ThirdPartyType $type) => [$type->value => $type->label()])->all())
                        ->required(),
                    TextInput::make('tax_id')
                        ->label('NIT/Cédula')
                        ->prefix('ID')
                        ->required()
                        ->maxLength(30),
                    TextInput::make('verification_digit')
                        ->label('DV')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(9),
                ]),
                TextInput::make('name')
                    ->label('Nombre/Razón social')
                    ->required()
                    ->maxLength(255),
            ]),
            Section::make('Contacto')->schema([
                Grid::make(3)->schema([
                    TextInput::make('email')->label('Correo')->email(),
                    TextInput::make('phone')->label('Teléfono')->tel(),
                    TextInput::make('city')->label('Ciudad'),
                ]),
                Textarea::make('address')->label('Dirección')->columnSpanFull(),
            ]),
        ]);
    }
}

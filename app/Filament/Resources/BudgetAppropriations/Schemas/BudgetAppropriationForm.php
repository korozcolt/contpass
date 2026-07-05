<?php

namespace App\Filament\Resources\BudgetAppropriations\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BudgetAppropriationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id)
                    ->required(),
                TextInput::make('fiscal_year')
                    ->label('Año fiscal')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2099)
                    ->default(now()->year)
                    ->required(),
                TextInput::make('code')
                    ->label('Código del rubro')
                    ->required()
                    ->maxLength(30),
                TextInput::make('name')
                    ->label('Nombre del rubro')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                AccountingFormFields::money('initial_amount', 'Apropiación inicial')
                    ->default(0),
                AccountingFormFields::money('additions', 'Adiciones')
                    ->default(0),
                AccountingFormFields::money('reductions', 'Reducciones')
                    ->default(0),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]);
    }
}

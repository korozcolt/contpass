<?php

namespace App\Filament\Resources\BudgetRevenues\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BudgetRevenueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id),
                TextInput::make('fiscal_year')
                    ->label('Vigencia')
                    ->numeric()
                    ->required()
                    ->default(date('Y'))
                    ->minValue(2000)
                    ->maxValue(2099),
                TextInput::make('code')
                    ->label('Código del rubro')
                    ->placeholder('1.1.01')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->label('Nombre del rubro')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('category')
                    ->label('Categoría')
                    ->options([
                        'corriente' => 'Ingresos Corrientes',
                        'capital' => 'Recursos de Capital',
                        'fondos_especiales' => 'Fondos Especiales',
                    ])
                    ->required()
                    ->default('corriente'),
                AccountingFormFields::money('projected_amount', 'Meta de recaudo proyectada'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }
}

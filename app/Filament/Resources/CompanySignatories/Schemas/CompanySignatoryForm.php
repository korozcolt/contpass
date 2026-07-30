<?php

namespace App\Filament\Resources\CompanySignatories\Schemas;

use App\Enums\SignatoryArea;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CompanySignatoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                Select::make('area')
                    ->label('Dependencia')
                    ->options(SignatoryArea::class)
                    ->required(),
                TextInput::make('full_name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(150),
                TextInput::make('position')
                    ->label('Cargo')
                    ->maxLength(150),
                TextInput::make('identification')
                    ->label('Cédula')
                    ->maxLength(30),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]),
        ]);
    }
}

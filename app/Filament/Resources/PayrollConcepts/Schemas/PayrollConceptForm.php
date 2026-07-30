<?php

namespace App\Filament\Resources\PayrollConcepts\Schemas;

use App\Enums\PayrollConceptType;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PayrollConceptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('code')->label('Código')->required()->maxLength(50),
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                Select::make('type')
                    ->label('Tipo')
                    ->options(PayrollConceptType::class)
                    ->required()
                    ->native(false),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]),
        ]);
    }
}

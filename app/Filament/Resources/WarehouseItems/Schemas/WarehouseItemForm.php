<?php

namespace App\Filament\Resources\WarehouseItems\Schemas;

use App\Enums\WarehouseItemType;
use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WarehouseItemForm
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
                    ->options(WarehouseItemType::class)
                    ->required()
                    ->native(false),
                TextInput::make('unit_of_measure')->label('Unidad de medida')->required()->maxLength(50)->placeholder('Unidad, Caja, Kg…'),
                TextInput::make('minimum_stock')->label('Stock mínimo')->numeric()->minValue(0)->step('0.01'),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]),
        ]);
    }
}

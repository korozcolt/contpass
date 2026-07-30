<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                TextInput::make('location')->label('Ubicación')->maxLength(255),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]),
        ]);
    }
}

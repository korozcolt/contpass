<?php

namespace App\Filament\Resources\WarehouseMovements\Schemas;

use App\Enums\WarehouseMovementType;
use App\Filament\Support\AccountingFormFields;
use App\Models\Dependency;
use App\Models\Warehouse;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WarehouseMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                Select::make('type')
                    ->label('Tipo de movimiento')
                    ->options(WarehouseMovementType::class)
                    ->required()
                    ->live()
                    ->native(false),
                self::warehouseSelect('warehouse_id', 'Almacén'),
                self::warehouseSelect('destination_warehouse_id', 'Almacén destino')
                    ->visible(fn (Get $get): bool => $get('type') === WarehouseMovementType::Transfer)
                    ->required(fn (Get $get): bool => $get('type') === WarehouseMovementType::Transfer),
                Select::make('dependency_id')
                    ->label('Dependencia destino')
                    ->options(fn (): array => Dependency::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => $get('type') === WarehouseMovementType::Exit),
                AccountingFormFields::thirdParty('third_party_id')
                    ->label('Proveedor')
                    ->required(false)
                    ->visible(fn (Get $get): bool => $get('type') === WarehouseMovementType::Entry),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->native(false)
                    ->default(today()),
                TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    private static function warehouseSelect(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (): array => Warehouse::query()
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->preload()
            ->required();
    }
}

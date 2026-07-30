<?php

namespace App\Filament\Resources\WarehouseMovements;

use App\Filament\Resources\WarehouseMovements\Pages\CreateWarehouseMovement;
use App\Filament\Resources\WarehouseMovements\Pages\EditWarehouseMovement;
use App\Filament\Resources\WarehouseMovements\Pages\ListWarehouseMovements;
use App\Filament\Resources\WarehouseMovements\RelationManagers\LinesRelationManager;
use App\Filament\Resources\WarehouseMovements\Schemas\WarehouseMovementForm;
use App\Filament\Resources\WarehouseMovements\Tables\WarehouseMovementsTable;
use App\Models\WarehouseMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarehouseMovementResource extends Resource
{
    protected static ?string $model = WarehouseMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static ?string $navigationLabel = 'Movimientos';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos';

    protected static string|\UnitEnum|null $navigationGroup = 'Almacén';

    public static function form(Schema $schema): Schema
    {
        return WarehouseMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseMovements::route('/'),
            'create' => CreateWarehouseMovement::route('/create'),
            'edit' => EditWarehouseMovement::route('/{record}/edit'),
        ];
    }
}

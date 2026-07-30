<?php

namespace App\Filament\Resources\WarehouseItems;

use App\Filament\Resources\WarehouseItems\Pages\CreateWarehouseItem;
use App\Filament\Resources\WarehouseItems\Pages\EditWarehouseItem;
use App\Filament\Resources\WarehouseItems\Pages\ListWarehouseItems;
use App\Filament\Resources\WarehouseItems\Schemas\WarehouseItemForm;
use App\Filament\Resources\WarehouseItems\Tables\WarehouseItemsTable;
use App\Models\WarehouseItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarehouseItemResource extends Resource
{
    protected static ?string $model = WarehouseItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cube;

    protected static ?string $navigationLabel = 'Elementos';

    protected static ?string $modelLabel = 'elemento';

    protected static ?string $pluralModelLabel = 'elementos';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    public static function form(Schema $schema): Schema
    {
        return WarehouseItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseItems::route('/'),
            'create' => CreateWarehouseItem::route('/create'),
            'edit' => EditWarehouseItem::route('/{record}/edit'),
        ];
    }
}

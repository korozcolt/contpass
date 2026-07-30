<?php

namespace App\Filament\Resources\Dependencies;

use App\Filament\Resources\Dependencies\Pages\CreateDependency;
use App\Filament\Resources\Dependencies\Pages\EditDependency;
use App\Filament\Resources\Dependencies\Pages\ListDependencies;
use App\Filament\Resources\Dependencies\Schemas\DependencyForm;
use App\Filament\Resources\Dependencies\Tables\DependenciesTable;
use App\Models\Dependency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DependencyResource extends Resource
{
    protected static ?string $model = Dependency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $navigationLabel = 'Dependencias';

    protected static ?string $modelLabel = 'dependencia';

    protected static ?string $pluralModelLabel = 'dependencias';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    public static function form(Schema $schema): Schema
    {
        return DependencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DependenciesTable::configure($table);
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
            'index' => ListDependencies::route('/'),
            'create' => CreateDependency::route('/create'),
            'edit' => EditDependency::route('/{record}/edit'),
        ];
    }
}

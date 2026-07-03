<?php

namespace App\Filament\Resources\ThirdParties;

use App\Filament\Resources\ThirdParties\Pages\CreateThirdParty;
use App\Filament\Resources\ThirdParties\Pages\EditThirdParty;
use App\Filament\Resources\ThirdParties\Pages\ListThirdParties;
use App\Filament\Resources\ThirdParties\Schemas\ThirdPartyForm;
use App\Filament\Resources\ThirdParties\Tables\ThirdPartiesTable;
use App\Models\ThirdParty;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ThirdPartyResource extends Resource
{
    protected static ?string $model = ThirdParty::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $navigationLabel = 'Terceros';

    protected static ?string $modelLabel = 'tercero';

    protected static ?string $pluralModelLabel = 'terceros';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    public static function form(Schema $schema): Schema
    {
        return ThirdPartyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThirdPartiesTable::configure($table);
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
            'index' => ListThirdParties::route('/'),
            'create' => CreateThirdParty::route('/create'),
            'edit' => EditThirdParty::route('/{record}/edit'),
        ];
    }
}

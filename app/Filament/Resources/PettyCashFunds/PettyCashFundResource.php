<?php

namespace App\Filament\Resources\PettyCashFunds;

use App\Filament\Resources\PettyCashFunds\Pages\CreatePettyCashFund;
use App\Filament\Resources\PettyCashFunds\Pages\EditPettyCashFund;
use App\Filament\Resources\PettyCashFunds\Pages\ListPettyCashFunds;
use App\Filament\Resources\PettyCashFunds\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\PettyCashFunds\Schemas\PettyCashFundForm;
use App\Filament\Resources\PettyCashFunds\Tables\PettyCashFundsTable;
use App\Models\PettyCashFund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PettyCashFundResource extends Resource
{
    protected static ?string $model = PettyCashFund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Wallet;

    protected static ?string $navigationLabel = 'Caja Menor';

    protected static ?string $modelLabel = 'fondo de caja menor';

    protected static ?string $pluralModelLabel = 'fondos de caja menor';

    protected static string|\UnitEnum|null $navigationGroup = 'Tesorería';

    public static function form(Schema $schema): Schema
    {
        return PettyCashFundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PettyCashFundsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPettyCashFunds::route('/'),
            'create' => CreatePettyCashFund::route('/create'),
            'edit' => EditPettyCashFund::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ChartAccounts;

use App\Filament\Resources\ChartAccounts\Pages\CreateChartAccount;
use App\Filament\Resources\ChartAccounts\Pages\EditChartAccount;
use App\Filament\Resources\ChartAccounts\Pages\ListChartAccounts;
use App\Filament\Resources\ChartAccounts\Schemas\ChartAccountForm;
use App\Filament\Resources\ChartAccounts\Tables\ChartAccountsTable;
use App\Models\ChartAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChartAccountResource extends Resource
{
    protected static ?string $model = ChartAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ChartAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChartAccountsTable::configure($table);
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
            'index' => ListChartAccounts::route('/'),
            'create' => CreateChartAccount::route('/create'),
            'edit' => EditChartAccount::route('/{record}/edit'),
        ];
    }
}

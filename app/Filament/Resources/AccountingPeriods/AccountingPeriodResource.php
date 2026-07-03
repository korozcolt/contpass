<?php

namespace App\Filament\Resources\AccountingPeriods;

use App\Filament\Resources\AccountingPeriods\Pages\CreateAccountingPeriod;
use App\Filament\Resources\AccountingPeriods\Pages\EditAccountingPeriod;
use App\Filament\Resources\AccountingPeriods\Pages\ListAccountingPeriods;
use App\Filament\Resources\AccountingPeriods\Schemas\AccountingPeriodForm;
use App\Filament\Resources\AccountingPeriods\Tables\AccountingPeriodsTable;
use App\Models\AccountingPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccountingPeriodResource extends Resource
{
    protected static ?string $model = AccountingPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AccountingPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountingPeriodsTable::configure($table);
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
            'index' => ListAccountingPeriods::route('/'),
            'create' => CreateAccountingPeriod::route('/create'),
            'edit' => EditAccountingPeriod::route('/{record}/edit'),
        ];
    }
}

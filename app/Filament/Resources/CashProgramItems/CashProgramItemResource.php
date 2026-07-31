<?php

namespace App\Filament\Resources\CashProgramItems;

use App\Filament\Resources\CashProgramItems\Pages\CreateCashProgramItem;
use App\Filament\Resources\CashProgramItems\Pages\EditCashProgramItem;
use App\Filament\Resources\CashProgramItems\Pages\ListCashProgramItems;
use App\Filament\Resources\CashProgramItems\Schemas\CashProgramItemForm;
use App\Filament\Resources\CashProgramItems\Tables\CashProgramItemsTable;
use App\Models\CashProgramItem;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashProgramItemResource extends Resource
{
    protected static ?string $model = CashProgramItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $navigationLabel = 'Programación Anual de Caja';

    protected static ?string $modelLabel = 'ítem de P.A.C.';

    protected static ?string $pluralModelLabel = 'Programación Anual de Caja';

    protected static string|\UnitEnum|null $navigationGroup = 'Tesorería';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return CashProgramItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashProgramItemsTable::configure($table);
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
            'index' => ListCashProgramItems::route('/'),
            'create' => CreateCashProgramItem::route('/create'),
            'edit' => EditCashProgramItem::route('/{record}/edit'),
        ];
    }
}

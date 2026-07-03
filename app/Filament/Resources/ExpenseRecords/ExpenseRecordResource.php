<?php

namespace App\Filament\Resources\ExpenseRecords;

use App\Filament\Resources\ExpenseRecords\Pages\CreateExpenseRecord;
use App\Filament\Resources\ExpenseRecords\Pages\ListExpenseRecords;
use App\Filament\Resources\ExpenseRecords\Schemas\ExpenseRecordForm;
use App\Filament\Resources\ExpenseRecords\Tables\ExpenseRecordsTable;
use App\Models\ExpenseRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExpenseRecordResource extends Resource
{
    protected static ?string $model = ExpenseRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowTrendingDown;

    protected static ?string $navigationLabel = 'Egresos';

    protected static ?string $modelLabel = 'egreso';

    protected static ?string $pluralModelLabel = 'egresos';

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    public static function form(Schema $schema): Schema
    {
        return ExpenseRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseRecordsTable::configure($table);
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
            'index' => ListExpenseRecords::route('/'),
            'create' => CreateExpenseRecord::route('/create'),
        ];
    }
}

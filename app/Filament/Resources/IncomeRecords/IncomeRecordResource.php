<?php

namespace App\Filament\Resources\IncomeRecords;

use App\Filament\Resources\IncomeRecords\Pages\CreateIncomeRecord;
use App\Filament\Resources\IncomeRecords\Pages\ListIncomeRecords;
use App\Filament\Resources\IncomeRecords\Schemas\IncomeRecordForm;
use App\Filament\Resources\IncomeRecords\Tables\IncomeRecordsTable;
use App\Models\IncomeRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IncomeRecordResource extends Resource
{
    protected static ?string $model = IncomeRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IncomeRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncomeRecordsTable::configure($table);
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
            'index' => ListIncomeRecords::route('/'),
            'create' => CreateIncomeRecord::route('/create'),
        ];
    }
}

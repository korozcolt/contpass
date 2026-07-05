<?php

namespace App\Filament\Resources\BudgetAppropriations;

use App\Filament\Resources\BudgetAppropriations\Pages\CreateBudgetAppropriation;
use App\Filament\Resources\BudgetAppropriations\Pages\EditBudgetAppropriation;
use App\Filament\Resources\BudgetAppropriations\Pages\ListBudgetAppropriations;
use App\Filament\Resources\BudgetAppropriations\Schemas\BudgetAppropriationForm;
use App\Filament\Resources\BudgetAppropriations\Tables\BudgetAppropriationsTable;
use App\Models\BudgetAppropriation;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetAppropriationResource extends Resource
{
    protected static ?string $model = BudgetAppropriation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $navigationLabel = 'Apropiaciones';

    protected static ?string $modelLabel = 'apropiación';

    protected static ?string $pluralModelLabel = 'apropiaciones';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetAppropriationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetAppropriationsTable::configure($table);
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
            'index' => ListBudgetAppropriations::route('/'),
            'create' => CreateBudgetAppropriation::route('/create'),
            'edit' => EditBudgetAppropriation::route('/{record}/edit'),
        ];
    }
}

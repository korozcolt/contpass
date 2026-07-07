<?php

namespace App\Filament\Resources\BudgetModifications;

use App\Filament\Resources\BudgetModifications\Pages\CreateBudgetModification;
use App\Filament\Resources\BudgetModifications\Pages\EditBudgetModification;
use App\Filament\Resources\BudgetModifications\Pages\ListBudgetModifications;
use App\Filament\Resources\BudgetModifications\Schemas\BudgetModificationForm;
use App\Filament\Resources\BudgetModifications\Tables\BudgetModificationsTable;
use App\Models\BudgetModification;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetModificationResource extends Resource
{
    protected static ?string $model = BudgetModification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PencilSquare;

    protected static ?string $navigationLabel = 'Modificaciones Presupuestales';

    protected static ?string $modelLabel = 'modificación presupuestal';

    protected static ?string $pluralModelLabel = 'modificaciones presupuestales';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto de Gastos';

    protected static ?int $navigationSort = 2;

    protected static ?string $globalSearchKeyAttribute = 'document_reference';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetModificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetModificationsTable::configure($table);
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
            'index' => ListBudgetModifications::route('/'),
            'create' => CreateBudgetModification::route('/create'),
            'edit' => EditBudgetModification::route('/{record}/edit'),
        ];
    }
}

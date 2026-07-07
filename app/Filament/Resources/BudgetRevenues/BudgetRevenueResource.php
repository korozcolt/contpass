<?php

namespace App\Filament\Resources\BudgetRevenues;

use App\Filament\Resources\BudgetRevenues\Pages\CreateBudgetRevenue;
use App\Filament\Resources\BudgetRevenues\Pages\EditBudgetRevenue;
use App\Filament\Resources\BudgetRevenues\Pages\ListBudgetRevenues;
use App\Filament\Resources\BudgetRevenues\Schemas\BudgetRevenueForm;
use App\Filament\Resources\BudgetRevenues\Tables\BudgetRevenuesTable;
use App\Models\BudgetRevenue;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetRevenueResource extends Resource
{
    protected static ?string $model = BudgetRevenue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowTrendingUp;

    protected static ?string $navigationLabel = 'Rubros de Ingresos';

    protected static ?string $modelLabel = 'rubro de ingresos';

    protected static ?string $pluralModelLabel = 'rubros de ingresos';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto de Ingresos';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetRevenueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetRevenuesTable::configure($table);
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
            'index' => ListBudgetRevenues::route('/'),
            'create' => CreateBudgetRevenue::route('/create'),
            'edit' => EditBudgetRevenue::route('/{record}/edit'),
        ];
    }
}

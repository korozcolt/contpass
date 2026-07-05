<?php

namespace App\Filament\Resources\BudgetObligations;

use App\Filament\Resources\BudgetObligations\Pages\CreateBudgetObligation;
use App\Filament\Resources\BudgetObligations\Pages\EditBudgetObligation;
use App\Filament\Resources\BudgetObligations\Pages\ListBudgetObligations;
use App\Filament\Resources\BudgetObligations\Schemas\BudgetObligationForm;
use App\Filament\Resources\BudgetObligations\Tables\BudgetObligationsTable;
use App\Models\BudgetObligation;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetObligationResource extends Resource
{
    protected static ?string $model = BudgetObligation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calculator;

    protected static ?string $navigationLabel = 'Obligaciones';

    protected static ?string $modelLabel = 'obligación';

    protected static ?string $pluralModelLabel = 'obligaciones';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetObligationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetObligationsTable::configure($table);
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
            'index' => ListBudgetObligations::route('/'),
            'create' => CreateBudgetObligation::route('/create'),
            'edit' => EditBudgetObligation::route('/{record}/edit'),
        ];
    }
}

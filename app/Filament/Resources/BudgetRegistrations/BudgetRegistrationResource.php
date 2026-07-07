<?php

namespace App\Filament\Resources\BudgetRegistrations;

use App\Filament\Resources\BudgetRegistrations\Pages\CreateBudgetRegistration;
use App\Filament\Resources\BudgetRegistrations\Pages\EditBudgetRegistration;
use App\Filament\Resources\BudgetRegistrations\Pages\ListBudgetRegistrations;
use App\Filament\Resources\BudgetRegistrations\RelationManagers\BudgetObligationsRelationManager;
use App\Filament\Resources\BudgetRegistrations\Schemas\BudgetRegistrationForm;
use App\Filament\Resources\BudgetRegistrations\Tables\BudgetRegistrationsTable;
use App\Models\BudgetRegistration;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetRegistrationResource extends Resource
{
    protected static ?string $model = BudgetRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'RPs';

    protected static ?string $modelLabel = 'Registro Presupuestal';

    protected static ?string $pluralModelLabel = 'Registros Presupuestales';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto de Gastos';

    /**
     * Oculto del menú lateral: accesible vía RelationManager dentro del CDP
     * y localizable en el buscador global por número de RP.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $globalSearchKeyAttribute = 'number';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetRegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetRegistrationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BudgetObligationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetRegistrations::route('/'),
            'create' => CreateBudgetRegistration::route('/create'),
            'edit' => EditBudgetRegistration::route('/{record}/edit'),
        ];
    }
}

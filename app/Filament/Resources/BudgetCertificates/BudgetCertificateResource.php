<?php

namespace App\Filament\Resources\BudgetCertificates;

use App\Filament\Resources\BudgetCertificates\Pages\CreateBudgetCertificate;
use App\Filament\Resources\BudgetCertificates\Pages\EditBudgetCertificate;
use App\Filament\Resources\BudgetCertificates\Pages\ListBudgetCertificates;
use App\Filament\Resources\BudgetCertificates\RelationManagers\BudgetRegistrationsRelationManager;
use App\Filament\Resources\BudgetCertificates\Schemas\BudgetCertificateForm;
use App\Filament\Resources\BudgetCertificates\Tables\BudgetCertificatesTable;
use App\Models\BudgetAvailabilityCertificate;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetCertificateResource extends Resource
{
    protected static ?string $model = BudgetAvailabilityCertificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'CDPs';

    protected static ?string $modelLabel = 'CDP';

    protected static ?string $pluralModelLabel = 'CDPs';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto de Gastos';

    /**
     * Oculto del menú lateral: accesible vía RelationManager dentro de Apropiación
     * y localizable en el buscador global por número de CDP.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $globalSearchKeyAttribute = 'number';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetCertificateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetCertificatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BudgetRegistrationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetCertificates::route('/'),
            'create' => CreateBudgetCertificate::route('/create'),
            'edit' => EditBudgetCertificate::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CompanySignatories;

use App\Filament\Resources\CompanySignatories\Pages\CreateCompanySignatory;
use App\Filament\Resources\CompanySignatories\Pages\EditCompanySignatory;
use App\Filament\Resources\CompanySignatories\Pages\ListCompanySignatories;
use App\Filament\Resources\CompanySignatories\Schemas\CompanySignatoryForm;
use App\Filament\Resources\CompanySignatories\Tables\CompanySignatoriesTable;
use App\Models\CompanySignatory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanySignatoryResource extends Resource
{
    protected static ?string $model = CompanySignatory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PencilSquare;

    protected static ?string $navigationLabel = 'Responsables firmantes';

    protected static ?string $modelLabel = 'responsable firmante';

    protected static ?string $pluralModelLabel = 'responsables firmantes';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    public static function form(Schema $schema): Schema
    {
        return CompanySignatoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanySignatoriesTable::configure($table);
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
            'index' => ListCompanySignatories::route('/'),
            'create' => CreateCompanySignatory::route('/create'),
            'edit' => EditCompanySignatory::route('/{record}/edit'),
        ];
    }
}

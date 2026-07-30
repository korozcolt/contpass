<?php

namespace App\Filament\Resources\PayrollConcepts;

use App\Filament\Resources\PayrollConcepts\Pages\CreatePayrollConcept;
use App\Filament\Resources\PayrollConcepts\Pages\EditPayrollConcept;
use App\Filament\Resources\PayrollConcepts\Pages\ListPayrollConcepts;
use App\Filament\Resources\PayrollConcepts\Schemas\PayrollConceptForm;
use App\Filament\Resources\PayrollConcepts\Tables\PayrollConceptsTable;
use App\Models\PayrollConcept;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollConceptResource extends Resource
{
    protected static ?string $model = PayrollConcept::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ListBullet;

    protected static ?string $navigationLabel = 'Conceptos';

    protected static ?string $modelLabel = 'concepto';

    protected static ?string $pluralModelLabel = 'conceptos';

    protected static string|\UnitEnum|null $navigationGroup = 'Nómina';

    public static function form(Schema $schema): Schema
    {
        return PayrollConceptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollConceptsTable::configure($table);
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
            'index' => ListPayrollConcepts::route('/'),
            'create' => CreatePayrollConcept::route('/create'),
            'edit' => EditPayrollConcept::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\PayrollFunds;

use App\Filament\Resources\PayrollFunds\Pages\CreatePayrollFund;
use App\Filament\Resources\PayrollFunds\Pages\EditPayrollFund;
use App\Filament\Resources\PayrollFunds\Pages\ListPayrollFunds;
use App\Filament\Resources\PayrollFunds\Schemas\PayrollFundForm;
use App\Filament\Resources\PayrollFunds\Tables\PayrollFundsTable;
use App\Models\PayrollFund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollFundResource extends Resource
{
    protected static ?string $model = PayrollFund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;

    protected static ?string $navigationLabel = 'Fondos';

    protected static ?string $modelLabel = 'fondo';

    protected static ?string $pluralModelLabel = 'fondos';

    protected static string|\UnitEnum|null $navigationGroup = 'Nómina';

    public static function form(Schema $schema): Schema
    {
        return PayrollFundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollFundsTable::configure($table);
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
            'index' => ListPayrollFunds::route('/'),
            'create' => CreatePayrollFund::route('/create'),
            'edit' => EditPayrollFund::route('/{record}/edit'),
        ];
    }
}

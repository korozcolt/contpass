<?php

namespace App\Filament\Resources\CashAccounts;

use App\Filament\Resources\CashAccounts\Pages\CreateCashAccount;
use App\Filament\Resources\CashAccounts\Pages\EditCashAccount;
use App\Filament\Resources\CashAccounts\Pages\ListCashAccounts;
use App\Filament\Resources\CashAccounts\Schemas\CashAccountForm;
use App\Filament\Resources\CashAccounts\Tables\CashAccountsTable;
use App\Models\CashAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashAccountResource extends Resource
{
    protected static ?string $model = CashAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $navigationLabel = 'Caja y bancos';

    protected static ?string $modelLabel = 'cuenta de caja o banco';

    protected static ?string $pluralModelLabel = 'cuentas de caja y bancos';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    public static function form(Schema $schema): Schema
    {
        return CashAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashAccountsTable::configure($table);
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
            'index' => ListCashAccounts::route('/'),
            'create' => CreateCashAccount::route('/create'),
            'edit' => EditCashAccount::route('/{record}/edit'),
        ];
    }
}

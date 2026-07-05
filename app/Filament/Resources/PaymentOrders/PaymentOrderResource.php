<?php

namespace App\Filament\Resources\PaymentOrders;

use App\Filament\Resources\PaymentOrders\Pages\CreatePaymentOrder;
use App\Filament\Resources\PaymentOrders\Pages\EditPaymentOrder;
use App\Filament\Resources\PaymentOrders\Pages\ListPaymentOrders;
use App\Filament\Resources\PaymentOrders\Schemas\PaymentOrderForm;
use App\Filament\Resources\PaymentOrders\Tables\PaymentOrdersTable;
use App\Models\PaymentOrder;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentOrderResource extends Resource
{
    protected static ?string $model = PaymentOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $navigationLabel = 'Órdenes de pago';

    protected static ?string $modelLabel = 'orden de pago';

    protected static ?string $pluralModelLabel = 'órdenes de pago';

    protected static string|\UnitEnum|null $navigationGroup = 'Presupuesto';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentOrdersTable::configure($table);
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
            'index' => ListPaymentOrders::route('/'),
            'create' => CreatePaymentOrder::route('/create'),
            'edit' => EditPaymentOrder::route('/{record}/edit'),
        ];
    }
}

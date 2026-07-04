<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Filament\Support\AccountingFormFields;
use App\Models\CashAccount;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                AccountingFormFields::voucher()->required(false),
                Select::make('cash_account_id')
                    ->label('Caja/Banco')
                    ->options(fn (): array => CashAccount::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                AccountingFormFields::chartAccount('counterparty_account_id', 'Contrapartida contable'),
                Select::make('method')
                    ->label('Medio de pago')
                    ->options(PaymentMethod::class)
                    ->helperText('Si selecciona efectivo, el sistema marcará el pago como no bancarizado.')
                    ->required(),
                TextInput::make('reference')->label('Referencia')->prefix('#')->maxLength(120),
                AccountingFormFields::date('paid_on', 'Fecha de pago'),
                AccountingFormFields::money('amount', 'Valor pagado'),
                TextInput::make('description')->label('Descripción')->columnSpanFull(),
            ]),
        ]);
    }
}

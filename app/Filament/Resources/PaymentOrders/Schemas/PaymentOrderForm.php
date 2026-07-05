<?php

namespace App\Filament\Resources\PaymentOrders\Schemas;

use App\Enums\PaymentMethod;
use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetObligation;
use App\Models\CashAccount;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id)
                    ->required(),
                Select::make('budget_obligation_id')
                    ->label('Obligación')
                    ->options(fn (): array => BudgetObligation::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('status', 'approved')
                        ->whereDoesntHave('paymentOrder')
                        ->with('budgetRegistration.thirdParty')
                        ->orderBy('number')
                        ->get()
                        ->mapWithKeys(fn (BudgetObligation $obl) => [
                            $obl->id => "{$obl->number} · \$".number_format((float) $obl->amount, 2)." · {$obl->budgetRegistration->thirdParty->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('cash_account_id')
                    ->label('Cuenta bancaria')
                    ->options(fn (): array => CashAccount::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (CashAccount $ca) => [
                            $ca->id => "{$ca->name} · {$ca->chartAccount->code}",
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                AccountingFormFields::date('issued_on', 'Fecha de emisión'),
                AccountingFormFields::money('amount', 'Monto a pagar'),
                Select::make('method')
                    ->label('Medio de pago')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::BankTransfer->value)
                    ->required(),
                TextInput::make('reference')
                    ->label('Referencia')
                    ->maxLength(100),
                TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}

<?php

namespace App\Filament\Resources\BudgetRegistrations\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BudgetRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id)
                    ->required(),
                Select::make('budget_availability_certificate_id')
                    ->label('CDP')
                    ->options(fn (): array => BudgetAvailabilityCertificate::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('status', 'active')
                        ->with('budgetAppropriation')
                        ->orderBy('number')
                        ->get()
                        ->mapWithKeys(fn (BudgetAvailabilityCertificate $cdp) => [
                            $cdp->id => "{$cdp->number} · {$cdp->budgetAppropriation->name} (Saldo: \$".number_format($cdp->available_for_registration, 2).')',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('third_party_id')
                    ->label('Tercero')
                    ->options(fn (): array => ThirdParty::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (ThirdParty $tp) => [
                            $tp->id => "{$tp->tax_id}-{$tp->verification_digit} · {$tp->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                AccountingFormFields::date('issued_on', 'Fecha de emisión'),
                AccountingFormFields::money('amount', 'Monto del RP'),
                Textarea::make('justification')
                    ->label('Objeto del contrato')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}

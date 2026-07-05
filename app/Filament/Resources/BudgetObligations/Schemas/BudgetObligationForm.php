<?php

namespace App\Filament\Resources\BudgetObligations\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetRegistration;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BudgetObligationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id)
                    ->required(),
                Select::make('budget_registration_id')
                    ->label('Registro Presupuestal')
                    ->options(fn (): array => BudgetRegistration::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('status', 'active')
                        ->with(['thirdParty', 'budgetAvailabilityCertificate'])
                        ->orderBy('number')
                        ->get()
                        ->mapWithKeys(fn (BudgetRegistration $rp) => [
                            $rp->id => "{$rp->number} · {$rp->thirdParty->name} (Saldo: \$".number_format($rp->available_for_obligation, 2).')',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                AccountingFormFields::date('accrual_date', 'Fecha de causación'),
                AccountingFormFields::money('amount', 'Monto de la obligación'),
                TextInput::make('support_type')
                    ->label('Tipo de soporte')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('Factura, Cuenta de cobro, Contrato...'),
                TextInput::make('support_number')
                    ->label('Número de soporte')
                    ->required()
                    ->maxLength(100),
                TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}

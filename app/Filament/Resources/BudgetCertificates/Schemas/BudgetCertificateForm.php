<?php

namespace App\Filament\Resources\BudgetCertificates\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetAppropriation;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BudgetCertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id)
                    ->required(),
                Select::make('budget_appropriation_id')
                    ->label('Rubro presupuestal')
                    ->options(fn (): array => BudgetAppropriation::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->active()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn (BudgetAppropriation $rubro) => [
                            $rubro->id => "{$rubro->code} · {$rubro->name} (Disponible: \$".number_format($rubro->available_amount, 2).')',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                AccountingFormFields::date('issued_on', 'Fecha de emisión'),
                AccountingFormFields::money('amount', 'Monto del CDP'),
                AccountingFormFields::date('expires_on', 'Fecha de vencimiento')->required(false),
                Textarea::make('justification')
                    ->label('Objeto del gasto')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}

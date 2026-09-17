<?php

namespace App\Filament\Resources\ExpenseRecords\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Models\Municipality;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ExpenseRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                AccountingFormFields::thirdParty(),
                AccountingFormFields::date('accrual_date', 'Fecha de causación'),
                AccountingFormFields::chartAccountPrefixes('expense_account_id', 'Cuenta gasto/costo clase 5 o 6', ['5', '6']),
                AccountingFormFields::chartAccount('payable_account_id', 'Cuenta por pagar clase 2', '2'),
                AccountingFormFields::municipality('municipality_id')
                    ->label('Municipio de la operación')
                    ->helperText('Precargado desde el domicilio de la empresa. Edítalo si el gasto se originó en otro municipio.')
                    ->default(fn (): ?int => self::defaultMunicipalityId())
                    ->required(false),
                TextInput::make('support_type')->label('Tipo de soporte')->default('Factura/Cuenta de cobro')->required()->maxLength(120),
                TextInput::make('support_number')->label('Número de soporte')->prefix('#')->required()->maxLength(120),
                AccountingFormFields::money('amount', 'Valor del egreso'),
                TextInput::make('description')->label('Descripción'),
                Toggle::make('has_valid_support')->label('Soporte idóneo')->default(true),
                Toggle::make('is_deductible')->label('Deducible')->default(true),
            ]),
        ]);
    }

    private static function defaultMunicipalityId(): ?int
    {
        $company = app(CurrentCompany::class)->get();

        if (blank($company->dane_department_code) || blank($company->dane_municipality_code)) {
            return null;
        }

        return Municipality::query()
            ->where('code', $company->dane_municipality_code)
            ->whereHas('department', fn ($query) => $query->where('code', $company->dane_department_code))
            ->value('id');
    }
}

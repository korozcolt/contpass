<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\EmployeeContractType;
use App\Enums\PayrollFundType;
use App\Filament\Support\AccountingFormFields;
use App\Models\Dependency;
use App\Models\PayrollFund;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                TextInput::make('position')->label('Cargo')->required()->maxLength(150),
                TextInput::make('tax_id')->label('Cédula')->required()->maxLength(30),
                TextInput::make('verification_digit')
                    ->label('Dígito de verificación')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(9),
                Select::make('dependency_id')
                    ->label('Dependencia')
                    ->options(fn (): array => Dependency::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                Select::make('contract_type')
                    ->label('Tipo de contrato')
                    ->options(EmployeeContractType::class)
                    ->required()
                    ->native(false),
                self::fundSelect('pension_fund_id', 'Fondo de pensión', PayrollFundType::Pension),
                self::fundSelect('health_fund_id', 'Fondo de salud', PayrollFundType::Health),
                DatePicker::make('hire_date')
                    ->label('Fecha de ingreso')
                    ->required()
                    ->native(false)
                    ->default(today()),
                DatePicker::make('termination_date')
                    ->label('Fecha de retiro')
                    ->native(false),
                AccountingFormFields::money('base_salary', 'Salario base'),
                Toggle::make('is_active')->label('Activo')->default(true),
            ]),
        ]);
    }

    private static function fundSelect(string $name, string $label, PayrollFundType $type): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (): array => PayrollFund::query()
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->where('type', $type)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->preload();
    }
}

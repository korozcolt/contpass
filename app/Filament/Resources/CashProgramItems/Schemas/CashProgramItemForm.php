<?php

namespace App\Filament\Resources\CashProgramItems\Schemas;

use App\Enums\CashProgramMovementType;
use App\Filament\Support\AccountingFormFields;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CashProgramItemForm
{
    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id),
                Select::make('fiscal_year')
                    ->label('Vigencia')
                    ->options(fn (): array => collect(range(now()->year - 1, now()->year + 1))
                        ->mapWithKeys(fn (int $year) => [$year => (string) $year])
                        ->all())
                    ->default(now()->year)
                    ->required(),
                Select::make('movement_type')
                    ->label('Tipo de movimiento')
                    ->options(CashProgramMovementType::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('budget_appropriation_id', null);
                        $set('budget_revenue_id', null);
                    }),
                Select::make('month')
                    ->label('Mes')
                    ->options(self::months())
                    ->required(),
                AccountingFormFields::budgetAppropriation()
                    ->visible(fn (Get $get): bool => $get('movement_type') === CashProgramMovementType::Expense)
                    ->required(fn (Get $get): bool => $get('movement_type') === CashProgramMovementType::Expense),
                AccountingFormFields::budgetRevenue()
                    ->visible(fn (Get $get): bool => $get('movement_type') === CashProgramMovementType::Income)
                    ->required(fn (Get $get): bool => $get('movement_type') === CashProgramMovementType::Income),
                AccountingFormFields::money('projected_amount', 'Monto de caja proyectado')
                    ->columnSpanFull(),
            ]);
    }
}

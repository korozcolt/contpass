<?php

namespace App\Filament\Resources\PettyCashFunds\Schemas;

use App\Filament\Support\AccountingFormFields;
use App\Models\CashAccount;
use App\Models\Employee;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PettyCashFundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id),
                TextInput::make('name')
                    ->label('Nombre del fondo')
                    ->placeholder('Ej: Caja Menor Secretaría General')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                AccountingFormFields::money('authorized_amount', 'Base fija autorizada')
                    ->columnSpanFull(),
                Select::make('employee_id')
                    ->label('Custodio')
                    ->options(fn (): array => Employee::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                Select::make('cash_account_id')
                    ->label('Cuenta de caja asociada')
                    ->options(fn (): array => CashAccount::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\AccountingPeriods\Schemas;

use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AccountingPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            AccountingFormFields::companyId(),
            Grid::make(2)->schema([
                AccountingFormFields::date('starts_on', 'Inicio del periodo'),
                AccountingFormFields::date('ends_on', 'Fin del periodo'),
                Toggle::make('is_closed')->label('Periodo cerrado')->default(false),
                DateTimePicker::make('closed_at')->label('Fecha de cierre')->native(false),
            ]),
        ]);
    }
}

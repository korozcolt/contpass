<?php

namespace App\Filament\Widgets;

use App\Models\ExpenseRecord;
use App\Models\IncomeRecord;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountingStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $company = app(CurrentCompany::class)->get();

        return [
            Stat::make('Ingresos causados', 'COP $'.number_format((float) IncomeRecord::query()->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))->sum('amount'), 2))
                ->description('Derechos reconocidos por causación'),
            Stat::make('Egresos causados', 'COP $'.number_format((float) ExpenseRecord::query()->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))->sum('amount'), 2))
                ->description('Costos y gastos registrados'),
            Stat::make('Pagos no bancarizados', Payment::query()->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))->where('is_bancarized', false)->count())
                ->description('Revisar Art. 771-5 E.T.'),
            Stat::make('Comprobantes', Voucher::query()->whereBelongsTo($company)->count())
                ->description('Incluye ajustes y pagos'),
        ];
    }
}

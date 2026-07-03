<?php

namespace App\Filament\Pages;

use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class TrialBalanceReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $navigationLabel = 'Balance';

    protected static ?string $title = 'Balance de comprobación';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.trial-balance-report';

    public function rows()
    {
        $company = app(CurrentCompany::class)->get();

        return DB::table('accounting_entries')
            ->select('chart_accounts.code', 'chart_accounts.name', DB::raw('sum(accounting_entries.debit) as debit_total'), DB::raw('sum(accounting_entries.credit) as credit_total'))
            ->join('chart_accounts', 'chart_accounts.id', '=', 'accounting_entries.chart_account_id')
            ->join('vouchers', 'vouchers.id', '=', 'accounting_entries.voucher_id')
            ->where('vouchers.company_id', $company->id)
            ->when(request('starts_on'), fn ($query, $date) => $query->whereDate('vouchers.date', '>=', $date))
            ->when(request('ends_on'), fn ($query, $date) => $query->whereDate('vouchers.date', '<=', $date))
            ->groupBy('chart_accounts.id', 'chart_accounts.code', 'chart_accounts.name')
            ->orderBy('chart_accounts.code')
            ->get();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AccountingEntry;
use App\Models\ExpenseRecord;
use App\Models\IncomeRecord;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CurrentCompany $currentCompany): View
    {
        $company = $currentCompany->get();

        return view('dashboard.index', [
            'company' => $company,
            'incomeTotal' => IncomeRecord::query()->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))->sum('amount'),
            'expenseTotal' => ExpenseRecord::query()->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))->sum('amount'),
            'paymentTotal' => Payment::query()->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))->sum('amount'),
            'voucherCount' => Voucher::query()->whereBelongsTo($company)->count(),
            'recentEntries' => AccountingEntry::query()
                ->with(['voucher', 'chartAccount', 'thirdParty'])
                ->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}

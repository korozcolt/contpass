<?php

namespace App\Filament\Pages;

use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LedgerReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static ?string $navigationLabel = 'Libro auxiliar';

    protected static ?string $title = 'Libro auxiliar';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.ledger-report';

    public function entries(): LengthAwarePaginator
    {
        return AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereHas('voucher', fn ($query) => $query->whereBelongsTo(app(CurrentCompany::class)->get()))
            ->when(request('starts_on'), fn ($query, $date) => $query->whereHas('voucher', fn ($query) => $query->whereDate('date', '>=', $date)))
            ->when(request('ends_on'), fn ($query, $date) => $query->whereHas('voucher', fn ($query) => $query->whereDate('date', '<=', $date)))
            ->when(request('chart_account_id'), fn ($query, $id) => $query->where('chart_account_id', $id))
            ->when(request('third_party_id'), fn ($query, $id) => $query->where('third_party_id', $id))
            ->latest()
            ->paginate(50)
            ->withQueryString();
    }

    public function chartAccounts()
    {
        return ChartAccount::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('code')->get();
    }

    public function thirdParties()
    {
        return ThirdParty::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('name')->get();
    }
}

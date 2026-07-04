<?php

namespace App\Filament\Pages;

use App\Models\AccountingEntry;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ThirdPartyMovementsReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $navigationLabel = 'Movimientos por tercero';

    protected static ?string $title = 'Movimientos por tercero';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.third-party-movements-report';

    public function entries(): LengthAwarePaginator
    {
        return AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereNotNull('third_party_id')
            ->whereHas('voucher', fn ($query) => $query->whereBelongsTo(app(CurrentCompany::class)->get()))
            ->when(request('starts_on'), fn ($query, $date) => $query->whereHas('voucher', fn ($query) => $query->whereDate('date', '>=', $date)))
            ->when(request('ends_on'), fn ($query, $date) => $query->whereHas('voucher', fn ($query) => $query->whereDate('date', '<=', $date)))
            ->when(request('third_party_id'), fn ($query, $id) => $query->where('third_party_id', $id))
            ->latest()
            ->paginate(50)
            ->withQueryString();
    }

    public function thirdParties()
    {
        return ThirdParty::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('name')->get();
    }
}

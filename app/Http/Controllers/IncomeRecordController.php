<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRecordRequest;
use App\Models\ChartAccount;
use App\Models\IncomeRecord;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\PostIncomeVoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IncomeRecordController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('income-records.index', [
            'incomeRecords' => IncomeRecord::query()
                ->with('voucher.thirdParty')
                ->whereHas('voucher', fn ($query) => $query->whereBelongsTo($this->currentCompany->get()))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('income-records.form', $this->formData());
    }

    public function store(StoreIncomeRecordRequest $request, PostIncomeVoucher $postIncomeVoucher): RedirectResponse
    {
        $thirdParty = ThirdParty::query()->findOrFail($request->integer('third_party_id'));
        $voucher = $postIncomeVoucher->handle($this->currentCompany->get(), $thirdParty, $request->validated());

        return redirect()->route('income-records.show', $voucher->incomeRecord)->with('status', "Ingreso causado en {$voucher->number}.");
    }

    public function show(IncomeRecord $incomeRecord): View
    {
        return view('income-records.show', ['incomeRecord' => $incomeRecord->load('voucher.entries.chartAccount', 'voucher.thirdParty')]);
    }

    public function edit(IncomeRecord $incomeRecord): RedirectResponse
    {
        return redirect()->route('income-records.show', $incomeRecord)->with('status', 'Los comprobantes aprobados se corrigen con nota de ajuste.');
    }

    public function update(StoreIncomeRecordRequest $request, IncomeRecord $incomeRecord): RedirectResponse
    {
        return redirect()->route('income-records.show', $incomeRecord)->with('status', 'Los comprobantes aprobados se corrigen con nota de ajuste.');
    }

    public function destroy(IncomeRecord $incomeRecord): RedirectResponse
    {
        return redirect()->route('income-records.show', $incomeRecord)->with('status', 'Los comprobantes aprobados no se eliminan directamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $company = $this->currentCompany->get();

        return [
            'thirdParties' => ThirdParty::query()->whereBelongsTo($company)->orderBy('name')->get(),
            'chartAccounts' => ChartAccount::query()->whereBelongsTo($company)->orderBy('code')->get(),
        ];
    }
}

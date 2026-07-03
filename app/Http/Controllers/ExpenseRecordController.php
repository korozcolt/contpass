<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRecordRequest;
use App\Models\ChartAccount;
use App\Models\ExpenseRecord;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\PostExpenseVoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseRecordController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('expense-records.index', [
            'expenseRecords' => ExpenseRecord::query()
                ->with('voucher.thirdParty')
                ->whereHas('voucher', fn ($query) => $query->whereBelongsTo($this->currentCompany->get()))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('expense-records.form', $this->formData());
    }

    public function store(StoreExpenseRecordRequest $request, PostExpenseVoucher $postExpenseVoucher): RedirectResponse
    {
        $thirdParty = ThirdParty::query()->findOrFail($request->integer('third_party_id'));
        $voucher = $postExpenseVoucher->handle($this->currentCompany->get(), $thirdParty, $request->validated());

        return redirect()->route('expense-records.show', $voucher->expenseRecord)->with('status', "Egreso causado en {$voucher->number}.");
    }

    public function show(ExpenseRecord $expenseRecord): View
    {
        return view('expense-records.show', ['expenseRecord' => $expenseRecord->load('voucher.entries.chartAccount', 'voucher.thirdParty')]);
    }

    public function edit(ExpenseRecord $expenseRecord): RedirectResponse
    {
        return redirect()->route('expense-records.show', $expenseRecord)->with('status', 'Los comprobantes aprobados se corrigen con nota de ajuste.');
    }

    public function update(StoreExpenseRecordRequest $request, ExpenseRecord $expenseRecord): RedirectResponse
    {
        return redirect()->route('expense-records.show', $expenseRecord)->with('status', 'Los comprobantes aprobados se corrigen con nota de ajuste.');
    }

    public function destroy(ExpenseRecord $expenseRecord): RedirectResponse
    {
        return redirect()->route('expense-records.show', $expenseRecord)->with('status', 'Los comprobantes aprobados no se eliminan directamente.');
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

<?php

namespace App\Http\Controllers;

use App\Enums\CashAccountType;
use App\Http\Requests\StoreCashAccountRequest;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Services\Accounting\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashAccountController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('cash-accounts.index', [
            'cashAccounts' => CashAccount::query()->with('chartAccount')->whereBelongsTo($this->currentCompany->get())->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('cash-accounts.form', $this->formData(new CashAccount));
    }

    public function store(StoreCashAccountRequest $request): RedirectResponse
    {
        $this->currentCompany->get()->cashAccounts()->create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('cash-accounts.index')->with('status', 'Caja/Banco creado.');
    }

    public function show(CashAccount $cashAccount): RedirectResponse
    {
        return redirect()->route('cash-accounts.edit', $cashAccount);
    }

    public function edit(CashAccount $cashAccount): View
    {
        return view('cash-accounts.form', $this->formData($cashAccount));
    }

    public function update(StoreCashAccountRequest $request, CashAccount $cashAccount): RedirectResponse
    {
        $cashAccount->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('cash-accounts.index')->with('status', 'Caja/Banco actualizado.');
    }

    public function destroy(CashAccount $cashAccount): RedirectResponse
    {
        $cashAccount->delete();

        return redirect()->route('cash-accounts.index')->with('status', 'Caja/Banco eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(CashAccount $cashAccount): array
    {
        return [
            'cashAccount' => $cashAccount,
            'types' => CashAccountType::cases(),
            'chartAccounts' => ChartAccount::query()->whereBelongsTo($this->currentCompany->get())->orderBy('code')->get(),
        ];
    }
}

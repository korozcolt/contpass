<?php

namespace App\Http\Controllers;

use App\Enums\AccountNature;
use App\Http\Requests\StoreChartAccountRequest;
use App\Models\ChartAccount;
use App\Services\Accounting\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChartAccountController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('chart-accounts.index', [
            'chartAccounts' => ChartAccount::query()->whereBelongsTo($this->currentCompany->get())->orderBy('code')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('chart-accounts.form', ['chartAccount' => new ChartAccount, 'natures' => AccountNature::cases()]);
    }

    public function store(StoreChartAccountRequest $request): RedirectResponse
    {
        $this->currentCompany->get()->chartAccounts()->create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('chart-accounts.index')->with('status', 'Cuenta PUC creada.');
    }

    public function show(ChartAccount $chartAccount): RedirectResponse
    {
        return redirect()->route('chart-accounts.edit', $chartAccount);
    }

    public function edit(ChartAccount $chartAccount): View
    {
        return view('chart-accounts.form', ['chartAccount' => $chartAccount, 'natures' => AccountNature::cases()]);
    }

    public function update(StoreChartAccountRequest $request, ChartAccount $chartAccount): RedirectResponse
    {
        $chartAccount->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('chart-accounts.index')->with('status', 'Cuenta PUC actualizada.');
    }

    public function destroy(ChartAccount $chartAccount): RedirectResponse
    {
        $chartAccount->delete();

        return redirect()->route('chart-accounts.index')->with('status', 'Cuenta PUC eliminada.');
    }
}

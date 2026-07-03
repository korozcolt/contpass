<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWithholdingRuleRequest;
use App\Models\ChartAccount;
use App\Models\WithholdingRule;
use App\Services\Accounting\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WithholdingRuleController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('withholding-rules.index', [
            'withholdingRules' => WithholdingRule::query()->with('chartAccount')->whereBelongsTo($this->currentCompany->get())->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('withholding-rules.form', $this->formData(new WithholdingRule));
    }

    public function store(StoreWithholdingRuleRequest $request): RedirectResponse
    {
        $this->currentCompany->get()->withholdingRules()->create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('withholding-rules.index')->with('status', 'Regla de retención creada.');
    }

    public function show(WithholdingRule $withholdingRule): RedirectResponse
    {
        return redirect()->route('withholding-rules.edit', $withholdingRule);
    }

    public function edit(WithholdingRule $withholdingRule): View
    {
        return view('withholding-rules.form', $this->formData($withholdingRule));
    }

    public function update(StoreWithholdingRuleRequest $request, WithholdingRule $withholdingRule): RedirectResponse
    {
        $withholdingRule->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('withholding-rules.index')->with('status', 'Regla de retención actualizada.');
    }

    public function destroy(WithholdingRule $withholdingRule): RedirectResponse
    {
        $withholdingRule->delete();

        return redirect()->route('withholding-rules.index')->with('status', 'Regla de retención eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(WithholdingRule $withholdingRule): array
    {
        return [
            'withholdingRule' => $withholdingRule,
            'chartAccounts' => ChartAccount::query()->whereBelongsTo($this->currentCompany->get())->orderBy('code')->get(),
        ];
    }
}

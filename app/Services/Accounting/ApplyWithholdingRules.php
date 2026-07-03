<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\WithholdingRule;
use Illuminate\Support\Collection;

class ApplyWithholdingRules
{
    /**
     * @return Collection<int, array{rule: WithholdingRule, amount: float}>
     */
    public function handle(Company $company, float $amount, string $date): Collection
    {
        return WithholdingRule::query()
            ->with('chartAccount')
            ->whereBelongsTo($company)
            ->where('is_active', true)
            ->effectiveOn($date)
            ->orderBy('concept')
            ->get()
            ->filter(fn (WithholdingRule $rule) => $amount >= (float) $rule->minimum_base)
            ->map(fn (WithholdingRule $rule) => [
                'rule' => $rule,
                'amount' => round($amount * ((float) $rule->rate / 100), 2),
            ])
            ->filter(fn (array $withholding) => $withholding['amount'] > 0)
            ->values();
    }
}

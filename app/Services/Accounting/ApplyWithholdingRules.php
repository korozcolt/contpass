<?php

namespace App\Services\Accounting;

use App\Enums\WithholdingType;
use App\Models\Company;
use App\Models\WithholdingRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApplyWithholdingRules
{
    /**
     * @return Collection<int, array{rule: WithholdingRule, amount: float}>
     */
    public function handle(Company $company, float $amount, string $date, ?int $municipalityId = null): Collection
    {
        return WithholdingRule::query()
            ->with('chartAccount')
            ->whereBelongsTo($company)
            ->where('is_active', true)
            ->effectiveOn($date)
            ->where(function (Builder $query) use ($municipalityId): void {
                $query->where('type', '!=', WithholdingType::Ica)
                    ->orWhere(function (Builder $query) use ($municipalityId): void {
                        $query->where('type', WithholdingType::Ica)
                            ->where('municipality_id', $municipalityId);
                    });
            })
            ->orderBy('type')
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

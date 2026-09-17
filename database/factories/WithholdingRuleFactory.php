<?php

namespace Database\Factories;

use App\Enums\AccountNature;
use App\Enums\WithholdingType;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Municipality;
use App\Models\WithholdingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithholdingRule>
 */
class WithholdingRuleFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'chart_account_id' => ChartAccount::factory()->state(['company_id' => $company, 'code' => fake()->unique()->numerify('2365##'), 'nature' => AccountNature::Credit]),
            'type' => WithholdingType::ReteFuente,
            'description' => 'Servicios',
            'municipality_id' => null,
            'minimum_base' => 100000,
            'rate' => 4,
            'starts_on' => '2026-01-01',
            'ends_on' => null,
            'is_active' => true,
        ];
    }

    public function ica(?Municipality $municipality = null): static
    {
        return $this->state(fn (): array => [
            'type' => WithholdingType::Ica,
            'municipality_id' => ($municipality ?? Municipality::factory()->create())->id,
            'description' => 'ICA',
            'rate' => 0.966,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\AccountingPeriod;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingPeriod>
 */
class AccountingPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->endOfMonth()->toDateString(),
            'is_closed' => false,
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['is_closed' => true, 'closed_at' => now()]);
    }
}

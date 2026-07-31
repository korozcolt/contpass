<?php

namespace Database\Factories;

use App\Enums\CashProgramMovementType;
use App\Models\CashProgramItem;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashProgramItem>
 */
class CashProgramItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'fiscal_year' => date('Y'),
            'movement_type' => CashProgramMovementType::Expense,
            'budget_appropriation_id' => null,
            'budget_revenue_id' => null,
            'month' => $this->faker->numberBetween(1, 12),
            'projected_amount' => $this->faker->randomFloat(2, 1_000_000, 100_000_000),
        ];
    }

    public function income(): static
    {
        return $this->state(['movement_type' => CashProgramMovementType::Income]);
    }

    public function expense(): static
    {
        return $this->state(['movement_type' => CashProgramMovementType::Expense]);
    }
}

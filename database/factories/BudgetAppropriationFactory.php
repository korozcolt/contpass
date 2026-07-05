<?php

namespace Database\Factories;

use App\Models\BudgetAppropriation;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetAppropriation>
 */
class BudgetAppropriationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'fiscal_year' => now()->year,
            'code' => $this->faker->unique()->numerify('##.##.##'),
            'name' => $this->faker->sentence(3),
            'initial_amount' => $this->faker->randomFloat(2, 10_000_000, 500_000_000),
            'additions' => 0,
            'reductions' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

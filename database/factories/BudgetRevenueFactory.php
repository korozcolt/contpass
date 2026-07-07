<?php

namespace Database\Factories;

use App\Models\BudgetRevenue;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetRevenue>
 */
class BudgetRevenueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id'       => Company::factory(),
            'fiscal_year'      => date('Y'),
            'code'             => $this->faker->numerify('#.#.##'),
            'name'             => $this->faker->words(3, true),
            'category'         => $this->faker->randomElement(['corriente', 'capital', 'fondos_especiales']),
            'projected_amount' => $this->faker->randomFloat(2, 1_000_000, 500_000_000),
            'is_active'        => true,
        ];
    }
}

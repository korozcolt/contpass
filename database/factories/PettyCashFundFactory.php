<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PettyCashFund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PettyCashFund>
 */
class PettyCashFundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => null,
            'cash_account_id' => null,
            'name' => 'Caja Menor '.$this->faker->words(2, true),
            'authorized_amount' => $this->faker->randomFloat(2, 500_000, 5_000_000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\PayrollFundType;
use App\Models\Company;
use App\Models\PayrollFund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollFund>
 */
class PayrollFundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'type' => fake()->randomElement(PayrollFundType::cases()),
            'nit' => fake()->numerify('900######'),
            'is_active' => true,
        ];
    }
}

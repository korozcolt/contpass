<?php

namespace Database\Factories;

use App\Enums\PayrollConceptType;
use App\Models\Company;
use App\Models\PayrollConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollConcept>
 */
class PayrollConceptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => fake()->unique()->numerify('CN###'),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(PayrollConceptType::cases()),
            'is_active' => true,
        ];
    }
}

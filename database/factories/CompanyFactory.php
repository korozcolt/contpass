<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'tax_id' => fake()->unique()->numerify('900######'),
            'verification_digit' => fake()->numberBetween(0, 9),
            'currency' => 'COP',
        ];
    }
}

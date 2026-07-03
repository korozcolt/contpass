<?php

namespace Database\Factories;

use App\Enums\ThirdPartyType;
use App\Models\Company;
use App\Models\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThirdParty>
 */
class ThirdPartyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => ThirdPartyType::LegalEntity,
            'name' => fake()->company(),
            'tax_id' => fake()->unique()->numerify('901######'),
            'verification_digit' => fake()->numberBetween(0, 9),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'address' => fake()->address(),
        ];
    }
}

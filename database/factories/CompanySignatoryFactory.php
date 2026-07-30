<?php

namespace Database\Factories;

use App\Enums\SignatoryArea;
use App\Models\Company;
use App\Models\CompanySignatory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySignatory>
 */
class CompanySignatoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'area' => fake()->randomElement(SignatoryArea::cases()),
            'full_name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'identification' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Dependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dependency>
 */
class DependencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Dependencia '.fake()->unique()->word(),
            'is_active' => true,
        ];
    }
}

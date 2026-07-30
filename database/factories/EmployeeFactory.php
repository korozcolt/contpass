<?php

namespace Database\Factories;

use App\Enums\EmployeeContractType;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'dependency_id' => null,
            'pension_fund_id' => null,
            'health_fund_id' => null,
            'tax_id' => fake()->unique()->numerify('##########'),
            'verification_digit' => fake()->numberBetween(0, 9),
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'contract_type' => fake()->randomElement(EmployeeContractType::cases()),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'termination_date' => null,
            'base_salary' => fake()->numberBetween(1300000, 8000000),
            'is_active' => true,
        ];
    }
}

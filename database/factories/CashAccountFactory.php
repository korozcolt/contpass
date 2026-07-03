<?php

namespace Database\Factories;

use App\Enums\CashAccountType;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashAccount>
 */
class CashAccountFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'chart_account_id' => ChartAccount::factory()->state(['company_id' => $company, 'code' => fake()->unique()->numerify('11##')]),
            'type' => CashAccountType::Bank,
            'name' => fake()->company().' Cuenta',
            'bank_name' => fake()->company(),
            'account_number' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ChartAccount;
use App\Models\IncomeRecord;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeRecord>
 */
class IncomeRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'revenue_account_id' => ChartAccount::factory()->credit(),
            'receivable_account_id' => ChartAccount::factory(),
            'support_number' => fake()->bothify('ING-####'),
            'accrual_date' => '2026-07-01',
            'amount' => 100000,
        ];
    }
}

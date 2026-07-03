<?php

namespace Database\Factories;

use App\Models\ChartAccount;
use App\Models\ExpenseRecord;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseRecord>
 */
class ExpenseRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'expense_account_id' => ChartAccount::factory(),
            'payable_account_id' => ChartAccount::factory()->credit(),
            'support_type' => 'Cuenta de cobro',
            'support_number' => fake()->bothify('EGR-####'),
            'accrual_date' => '2026-07-01',
            'amount' => 100000,
            'withholding_amount' => 0,
            'has_valid_support' => true,
            'is_deductible' => true,
        ];
    }
}

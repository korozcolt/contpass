<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentOrderStatus;
use App\Models\BudgetObligation;
use App\Models\CashAccount;
use App\Models\PaymentOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentOrder>
 */
class PaymentOrderFactory extends Factory
{
    public function definition(): array
    {
        $obligation = BudgetObligation::factory()->approved()->create();

        return [
            'company_id' => $obligation->company_id,
            'budget_obligation_id' => $obligation->id,
            'cash_account_id' => CashAccount::factory()->create(['company_id' => $obligation->company_id])->id,
            'voucher_id' => null,
            'number' => 'OP-'.now()->year.'-'.str_pad($this->faker->unique()->randomNumber(5), 6, '0', STR_PAD_LEFT),
            'status' => PaymentOrderStatus::Pending,
            'amount' => $obligation->amount,
            'method' => PaymentMethod::Transfer,
            'reference' => $this->faker->bothify('TRF-####'),
            'issued_on' => now()->toDateString(),
            'paid_on' => null,
            'description' => $this->faker->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => PaymentOrderStatus::Approved]);
    }

    public function paid(): static
    {
        return $this->state([
            'status' => PaymentOrderStatus::Paid,
            'paid_on' => now()->toDateString(),
        ]);
    }
}

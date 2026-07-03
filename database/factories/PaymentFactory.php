<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\CashAccount;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'source_voucher_id' => null,
            'cash_account_id' => CashAccount::factory(),
            'method' => PaymentMethod::BankTransfer,
            'reference' => fake()->bothify('REF-####'),
            'paid_on' => '2026-07-02',
            'amount' => 100000,
            'is_bancarized' => true,
        ];
    }
}

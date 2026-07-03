<?php

namespace Database\Factories;

use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\ThirdParty;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingEntry>
 */
class AccountingEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'chart_account_id' => ChartAccount::factory(),
            'third_party_id' => ThirdParty::factory(),
            'description' => fake()->sentence(4),
            'debit' => 100000,
            'credit' => 0,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\BankProfile;
use App\Models\BankStatementImport;
use App\Models\CashAccount;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankStatementImport>
 */
class BankStatementImportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'cash_account_id' => CashAccount::factory(),
            'bank' => BankProfile::Bancolombia,
            'file_name' => fake()->bothify('extracto-####.csv'),
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
        ];
    }
}

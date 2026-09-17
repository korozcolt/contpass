<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'third_party_id' => ThirdParty::factory()->state(['company_id' => $company]),
            'number' => fake()->unique()->bothify('COT-2026-#####'),
            'status' => QuotationStatus::Draft,
            'revenue_account_id' => ChartAccount::factory()->credit()->state(['company_id' => $company, 'code' => '413595']),
            'receivable_account_id' => ChartAccount::factory()->state(['company_id' => $company, 'code' => '130505']),
            'notes' => null,
            'expires_on' => now()->addDays(30)->toDateString(),
            'rejection_reason' => null,
            'voucher_id' => null,
        ];
    }
}

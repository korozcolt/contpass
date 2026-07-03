<?php

namespace Database\Factories;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'third_party_id' => ThirdParty::factory()->state(['company_id' => $company]),
            'adjusts_voucher_id' => null,
            'type' => VoucherType::Income,
            'status' => VoucherStatus::Approved,
            'number' => fake()->unique()->bothify('TES-2026-######'),
            'date' => '2026-07-01',
            'description' => fake()->sentence(4),
            'approved_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\BudgetRegistrationStatus;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetRegistration;
use App\Models\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetRegistration>
 */
class BudgetRegistrationFactory extends Factory
{
    public function definition(): array
    {
        $certificate = BudgetAvailabilityCertificate::factory()->create();

        return [
            'company_id' => $certificate->company_id,
            'budget_availability_certificate_id' => $certificate->id,
            'third_party_id' => ThirdParty::factory()->create(['company_id' => $certificate->company_id])->id,
            'number' => 'RP-'.now()->year.'-'.str_pad($this->faker->unique()->randomNumber(5), 6, '0', STR_PAD_LEFT),
            'status' => BudgetRegistrationStatus::Active,
            'fiscal_year' => now()->year,
            'amount' => $this->faker->randomFloat(2, 500_000, 20_000_000),
            'justification' => $this->faker->sentence(),
            'issued_on' => now()->toDateString(),
        ];
    }

    public function fullyObligated(): static
    {
        return $this->state(['status' => BudgetRegistrationStatus::FullyObligated]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BudgetRegistrationStatus::Cancelled]);
    }
}

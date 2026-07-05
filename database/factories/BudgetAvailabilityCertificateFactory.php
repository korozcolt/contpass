<?php

namespace Database\Factories;

use App\Enums\BudgetCertificateStatus;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetAvailabilityCertificate>
 */
class BudgetAvailabilityCertificateFactory extends Factory
{
    public function definition(): array
    {
        $appropriation = BudgetAppropriation::factory()->create();

        return [
            'company_id' => $appropriation->company_id,
            'budget_appropriation_id' => $appropriation->id,
            'number' => 'CDP-'.now()->year.'-'.str_pad($this->faker->unique()->randomNumber(5), 6, '0', STR_PAD_LEFT),
            'status' => BudgetCertificateStatus::Active,
            'fiscal_year' => now()->year,
            'amount' => $this->faker->randomFloat(2, 1_000_000, 50_000_000),
            'justification' => $this->faker->sentence(),
            'issued_on' => now()->toDateString(),
            'expires_on' => now()->addYear()->toDateString(),
        ];
    }

    public function fullyCommitted(): static
    {
        return $this->state(['status' => BudgetCertificateStatus::FullyCommitted]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BudgetCertificateStatus::Cancelled]);
    }
}

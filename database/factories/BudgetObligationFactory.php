<?php

namespace Database\Factories;

use App\Enums\BudgetObligationStatus;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetObligation>
 */
class BudgetObligationFactory extends Factory
{
    public function definition(): array
    {
        $registration = BudgetRegistration::factory()->create();

        return [
            'company_id' => $registration->company_id,
            'budget_registration_id' => $registration->id,
            'voucher_id' => null,
            'number' => 'OBL-'.now()->year.'-'.str_pad($this->faker->unique()->randomNumber(5), 6, '0', STR_PAD_LEFT),
            'status' => BudgetObligationStatus::Draft,
            'fiscal_year' => now()->year,
            'amount' => $this->faker->randomFloat(2, 100_000, 5_000_000),
            'support_type' => 'Factura',
            'support_number' => $this->faker->numerify('FV-####'),
            'accrual_date' => now()->toDateString(),
            'description' => $this->faker->sentence(),
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => BudgetObligationStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}

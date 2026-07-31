<?php

namespace Database\Factories;

use App\Enums\PettyCashMovementType;
use App\Models\PettyCashFund;
use App\Models\PettyCashMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PettyCashMovement>
 */
class PettyCashMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'petty_cash_fund_id' => PettyCashFund::factory(),
            'third_party_id' => null,
            'type' => PettyCashMovementType::Opening,
            'date' => now()->toDateString(),
            'amount' => $this->faker->randomFloat(2, 10_000, 500_000),
            'support_number' => null,
            'description' => $this->faker->sentence(),
        ];
    }

    public function opening(): static
    {
        return $this->state(['type' => PettyCashMovementType::Opening]);
    }

    public function expense(): static
    {
        return $this->state(['type' => PettyCashMovementType::Expense]);
    }

    public function replenishment(): static
    {
        return $this->state(['type' => PettyCashMovementType::Replenishment]);
    }

    public function closure(): static
    {
        return $this->state(['type' => PettyCashMovementType::Closure]);
    }
}

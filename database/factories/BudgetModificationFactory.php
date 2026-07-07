<?php

namespace Database\Factories;

use App\Enums\BudgetModificationType;
use App\Models\BudgetAppropriation;
use App\Models\BudgetModification;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetModification>
 */
class BudgetModificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(BudgetModificationType::cases())->value,
            'document_reference' => 'Decreto '.$this->faker->numerify('###').' de '.date('Y'),
            'source_appropriation_id' => null,
            'destination_appropriation_id' => BudgetAppropriation::factory(),
            'amount' => $this->faker->randomFloat(2, 100_000, 50_000_000),
            'justification' => $this->faker->sentence(),
            'effective_date' => $this->faker->dateThisYear(),
            'user_id' => User::factory(),
        ];
    }

    public function addition(): static
    {
        return $this->state(fn () => [
            'type' => BudgetModificationType::Addition->value,
            'source_appropriation_id' => null,
        ]);
    }

    public function reduction(): static
    {
        return $this->state(fn () => [
            'type' => BudgetModificationType::Reduction->value,
            'source_appropriation_id' => null,
        ]);
    }

    public function transfer(BudgetAppropriation $source, BudgetAppropriation $destination): static
    {
        return $this->state(fn () => [
            'type' => BudgetModificationType::Transfer->value,
            'source_appropriation_id' => $source->id,
            'destination_appropriation_id' => $destination->id,
        ]);
    }
}

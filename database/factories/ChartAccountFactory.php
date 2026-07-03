<?php

namespace Database\Factories;

use App\Enums\AccountNature;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartAccount>
 */
class ChartAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'nature' => AccountNature::Debit,
            'is_active' => true,
        ];
    }

    public function credit(): static
    {
        return $this->state(fn () => ['nature' => AccountNature::Credit]);
    }
}

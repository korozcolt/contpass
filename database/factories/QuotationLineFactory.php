<?php

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\QuotationLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationLine>
 */
class QuotationLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_id' => Quotation::factory(),
            'description' => fake()->words(3, true),
            'quantity' => 1,
            'unit_price' => fake()->randomFloat(2, 10000, 500000),
        ];
    }
}

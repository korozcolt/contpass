<?php

namespace Database\Factories;

use App\Models\ExternalInvoiceReference;
use App\Models\IncomeRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalInvoiceReference>
 */
class ExternalInvoiceReferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'income_record_id' => IncomeRecord::factory(),
            'invoice_number' => fake()->unique()->bothify('FE-####'),
            'cufe' => null,
            'provider' => fake()->company(),
            'document_url' => null,
        ];
    }
}

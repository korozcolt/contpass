<?php

namespace Database\Factories;

use App\Enums\BankStatementLineStatus;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankStatementLine>
 */
class BankStatementLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bank_statement_import_id' => BankStatementImport::factory(),
            'line_date' => '2026-07-05',
            'description' => fake()->sentence(3),
            'amount' => 100000,
            'reference' => fake()->bothify('REF-####'),
            'raw_row' => [],
            'status' => BankStatementLineStatus::Pending,
        ];
    }
}

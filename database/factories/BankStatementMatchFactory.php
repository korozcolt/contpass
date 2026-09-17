<?php

namespace Database\Factories;

use App\Enums\BankStatementMatchConfidence;
use App\Enums\BankStatementMatchStatus;
use App\Models\BankStatementLine;
use App\Models\BankStatementMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankStatementMatch>
 */
class BankStatementMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bank_statement_line_id' => BankStatementLine::factory(),
            'confidence' => BankStatementMatchConfidence::Candidate,
            'status' => BankStatementMatchStatus::Proposed,
        ];
    }
}

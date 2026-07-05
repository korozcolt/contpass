<?php

namespace Database\Factories;

use App\Models\BudgetAppropriation;
use App\Models\BudgetChartMapping;
use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetChartMapping>
 */
class BudgetChartMappingFactory extends Factory
{
    public function definition(): array
    {
        $appropriation = BudgetAppropriation::factory()->create();

        return [
            'company_id' => $appropriation->company_id,
            'budget_appropriation_id' => $appropriation->id,
            'expense_chart_account_id' => ChartAccount::factory()->create(['company_id' => $appropriation->company_id])->id,
            'payable_chart_account_id' => ChartAccount::factory()->create(['company_id' => $appropriation->company_id])->id,
        ];
    }
}

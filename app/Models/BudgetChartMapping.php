<?php

namespace App\Models;

use Database\Factories\BudgetChartMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetChartMapping extends Model
{
    /** @use HasFactory<BudgetChartMappingFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'budget_appropriation_id',
        'expense_chart_account_id',
        'payable_chart_account_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetAppropriation(): BelongsTo
    {
        return $this->belongsTo(BudgetAppropriation::class);
    }

    public function expenseChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'expense_chart_account_id');
    }

    public function payableChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'payable_chart_account_id');
    }
}

<?php

namespace App\Models;

use Database\Factories\ExpenseRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseRecord extends Model
{
    /** @use HasFactory<ExpenseRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'budget_obligation_id',
        'expense_account_id',
        'payable_account_id',
        'support_type',
        'support_number',
        'accrual_date',
        'amount',
        'withholding_amount',
        'has_valid_support',
        'is_deductible',
    ];

    protected function casts(): array
    {
        return [
            'accrual_date' => 'date',
            'amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'has_valid_support' => 'boolean',
            'is_deductible' => 'boolean',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'expense_account_id');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'payable_account_id');
    }

    public function budgetObligation(): BelongsTo
    {
        return $this->belongsTo(BudgetObligation::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\IncomeRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeRecord extends Model
{
    /** @use HasFactory<IncomeRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'revenue_account_id',
        'receivable_account_id',
        'support_number',
        'accrual_date',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'accrual_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'revenue_account_id');
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'receivable_account_id');
    }
}

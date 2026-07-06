<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentOrderStatus;
use App\Traits\Auditable;
use Database\Factories\PaymentOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    /** @use HasFactory<PaymentOrderFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'budget_obligation_id',
        'cash_account_id',
        'voucher_id',
        'number',
        'status',
        'amount',
        'method',
        'reference',
        'issued_on',
        'paid_on',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentOrderStatus::class,
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'issued_on' => 'date',
            'paid_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetObligation(): BelongsTo
    {
        return $this->belongsTo(BudgetObligation::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}

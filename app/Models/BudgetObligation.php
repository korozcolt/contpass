<?php

namespace App\Models;

use App\Enums\BudgetObligationStatus;
use App\Traits\Auditable;
use Database\Factories\BudgetObligationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BudgetObligation extends Model
{
    /** @use HasFactory<BudgetObligationFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'budget_registration_id',
        'voucher_id',
        'number',
        'status',
        'fiscal_year',
        'amount',
        'support_type',
        'support_number',
        'accrual_date',
        'description',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BudgetObligationStatus::class,
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'accrual_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetRegistration(): BelongsTo
    {
        return $this->belongsTo(BudgetRegistration::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function paymentOrder(): HasOne
    {
        return $this->hasOne(PaymentOrder::class);
    }

    public function scopeDraft($query): mixed
    {
        return $query->where('status', BudgetObligationStatus::Draft->value);
    }

    public function scopeApproved($query): mixed
    {
        return $query->where('status', BudgetObligationStatus::Approved->value);
    }
}

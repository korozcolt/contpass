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

    protected static function booted(): void
    {
        static::creating(function (PaymentOrder $order): void {
            if (blank($order->number)) {
                $fiscalYear = $order->issued_on ? (int) $order->issued_on->format('Y') : (int) now()->format('Y');
                $count = PaymentOrder::query()
                    ->where('company_id', $order->company_id)
                    ->whereYear('issued_on', $fiscalYear)
                    ->count() + 1;

                $order->number = sprintf('OP-%d-%06d', $fiscalYear, $count);
            }
        });
    }

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

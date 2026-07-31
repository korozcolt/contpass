<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'source_voucher_id',
        'payment_order_id',
        'cash_account_id',
        'method',
        'reference',
        'paid_on',
        'amount',
        'is_bancarized',
        'reconciled_at',
        'is_reconciled',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'paid_on' => 'date',
            'amount' => 'decimal:2',
            'is_bancarized' => 'boolean',
            'reconciled_at' => 'datetime',
        ];
    }

    protected function isReconciled(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->reconciled_at !== null,
            set: fn (bool $value): array => ['reconciled_at' => $value ? now() : null],
        );
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function sourceVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'source_voucher_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }
}

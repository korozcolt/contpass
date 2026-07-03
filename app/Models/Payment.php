<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
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
        'cash_account_id',
        'method',
        'reference',
        'paid_on',
        'amount',
        'is_bancarized',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'paid_on' => 'date',
            'amount' => 'decimal:2',
            'is_bancarized' => 'boolean',
        ];
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
}

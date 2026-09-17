<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use App\Traits\Auditable;
use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'third_party_id',
        'number',
        'status',
        'revenue_account_id',
        'receivable_account_id',
        'notes',
        'expires_on',
        'rejection_reason',
        'voucher_id',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'expires_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'revenue_account_id');
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'receivable_account_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class);
    }

    public function getTotalAttribute(): float
    {
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();

        return round((float) $lines->sum('subtotal'), 2);
    }

    public function effectiveStatus(): QuotationStatus
    {
        if ($this->status === QuotationStatus::Sent && $this->expires_on !== null && $this->expires_on->isPast()) {
            return QuotationStatus::Expired;
        }

        return $this->status;
    }

    public function isReadOnly(): bool
    {
        return $this->status === QuotationStatus::Converted;
    }
}

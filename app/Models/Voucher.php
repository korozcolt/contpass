<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use RuntimeException;

class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'third_party_id',
        'adjusts_voucher_id',
        'type',
        'status',
        'number',
        'date',
        'description',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'status' => VoucherStatus::class,
            'date' => 'date',
            'approved_at' => 'datetime',
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

    public function adjustsVoucher(): BelongsTo
    {
        return $this->belongsTo(self::class, 'adjusts_voucher_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }

    public function incomeRecord(): HasOne
    {
        return $this->hasOne(IncomeRecord::class);
    }

    public function expenseRecord(): HasOne
    {
        return $this->hasOne(ExpenseRecord::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isBalanced(): bool
    {
        $entries = $this->relationLoaded('entries') ? $this->entries : $this->entries()->get();

        return self::entriesAreBalanced($entries);
    }

    /**
     * @param  Collection<int, AccountingEntry>|array<int, array{debit?: numeric-string|float|int, credit?: numeric-string|float|int}>  $entries
     */
    public static function entriesAreBalanced(Collection|array $entries): bool
    {
        $debits = 0.0;
        $credits = 0.0;

        foreach ($entries as $entry) {
            $debits += (float) (is_array($entry) ? ($entry['debit'] ?? 0) : $entry->debit);
            $credits += (float) (is_array($entry) ? ($entry['credit'] ?? 0) : $entry->credit);
        }

        return round($debits, 2) === round($credits, 2) && round($debits, 2) > 0;
    }

    public function ensureEditable(): void
    {
        if ($this->status !== VoucherStatus::Draft) {
            throw new RuntimeException('Los comprobantes aprobados, anulados o ajustados no se modifican directamente.');
        }
    }
}

<?php

namespace App\Models;

use Database\Factories\WithholdingRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingRule extends Model
{
    /** @use HasFactory<WithholdingRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'chart_account_id',
        'concept',
        'minimum_base',
        'rate',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_base' => 'decimal:2',
            'rate' => 'decimal:4',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function scopeEffectiveOn(Builder $query, string $date): Builder
    {
        return $query->where('starts_on', '<=', $date)
            ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $date));
    }
}

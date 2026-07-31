<?php

namespace App\Models;

use App\Enums\PettyCashMovementType;
use App\Traits\Auditable;
use Database\Factories\PettyCashFundFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashFund extends Model
{
    /** @use HasFactory<PettyCashFundFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'cash_account_id',
        'name',
        'authorized_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'authorized_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Saldo disponible en efectivo: aperturas + reembolsos - gastos - cierres.
     */
    protected function availableBalance(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $increases = $this->movements()
                    ->whereIn('type', [PettyCashMovementType::Opening->value, PettyCashMovementType::Replenishment->value])
                    ->sum('amount');

                $decreases = $this->movements()
                    ->whereIn('type', [PettyCashMovementType::Expense->value, PettyCashMovementType::Closure->value])
                    ->sum('amount');

                return round((float) $increases - (float) $decreases, 2);
            }
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PettyCashMovement::class);
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }
}

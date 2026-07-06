<?php

namespace App\Models;

use App\Enums\BudgetObligationStatus;
use App\Enums\BudgetRegistrationStatus;
use App\Traits\Auditable;
use Database\Factories\BudgetRegistrationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetRegistration extends Model
{
    /** @use HasFactory<BudgetRegistrationFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'budget_availability_certificate_id',
        'third_party_id',
        'number',
        'status',
        'fiscal_year',
        'amount',
        'justification',
        'issued_on',
    ];

    protected function casts(): array
    {
        return [
            'status' => BudgetRegistrationStatus::class,
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'issued_on' => 'date',
        ];
    }

    /**
     * Saldo del RP disponible para nuevas Obligaciones.
     * Incluye obligaciones en draft y approved (no canceladas).
     */
    protected function availableForObligation(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $obligated = $this->budgetObligations()
                    ->whereNotIn('status', [BudgetObligationStatus::Cancelled->value])
                    ->sum('amount');

                return round((float) $this->amount - (float) $obligated, 2);
            }
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetAvailabilityCertificate(): BelongsTo
    {
        return $this->belongsTo(BudgetAvailabilityCertificate::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function budgetObligations(): HasMany
    {
        return $this->hasMany(BudgetObligation::class);
    }

    public function scopeActive($query): mixed
    {
        return $query->where('status', BudgetRegistrationStatus::Active->value);
    }
}

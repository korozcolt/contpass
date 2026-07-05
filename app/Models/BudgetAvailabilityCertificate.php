<?php

namespace App\Models;

use App\Enums\BudgetCertificateStatus;
use App\Enums\BudgetRegistrationStatus;
use Database\Factories\BudgetAvailabilityCertificateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetAvailabilityCertificate extends Model
{
    /** @use HasFactory<BudgetAvailabilityCertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'budget_appropriation_id',
        'number',
        'status',
        'fiscal_year',
        'amount',
        'justification',
        'issued_on',
        'expires_on',
    ];

    protected function casts(): array
    {
        return [
            'status' => BudgetCertificateStatus::class,
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
            'issued_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    /**
     * Saldo del CDP disponible para nuevos RPs.
     * Suma de RPs activos/obligados (no cancelados) sobre este CDP.
     */
    protected function availableForRegistration(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $committed = $this->budgetRegistrations()
                    ->whereNotIn('status', [BudgetRegistrationStatus::Cancelled->value])
                    ->sum('amount');

                return round((float) $this->amount - (float) $committed, 2);
            }
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetAppropriation(): BelongsTo
    {
        return $this->belongsTo(BudgetAppropriation::class);
    }

    public function budgetRegistrations(): HasMany
    {
        return $this->hasMany(BudgetRegistration::class);
    }

    public function scopeActive($query): mixed
    {
        return $query->where('status', BudgetCertificateStatus::Active->value);
    }
}

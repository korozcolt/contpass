<?php

namespace App\Models;

use App\Enums\BudgetCertificateStatus;
use App\Traits\Auditable;
use Database\Factories\BudgetAppropriationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BudgetAppropriation extends Model
{
    /** @use HasFactory<BudgetAppropriationFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'code',
        'name',
        'initial_amount',
        'additions',
        'reductions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'initial_amount' => 'decimal:2',
            'additions' => 'decimal:2',
            'reductions' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Apropiación total = inicial + adiciones - reducciones.
     */
    protected function totalAppropriation(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round(
                (float) $this->initial_amount + (float) $this->additions - (float) $this->reductions,
                2
            )
        );
    }

    /**
     * Saldo disponible del rubro = total - suma de CDPs activos/comprometidos.
     * Los saldos se calculan por agregación para evitar inconsistencias.
     */
    protected function availableAmount(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $reserved = $this->budgetCertificates()
                    ->whereNotIn('status', [BudgetCertificateStatus::Cancelled->value])
                    ->sum('amount');

                return round($this->total_appropriation - (float) $reserved, 2);
            }
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetCertificates(): HasMany
    {
        return $this->hasMany(BudgetAvailabilityCertificate::class);
    }

    public function budgetModifications(): HasMany
    {
        return $this->hasMany(BudgetModification::class, 'destination_appropriation_id');
    }

    public function chartMapping(): HasOne
    {
        return $this->hasOne(BudgetChartMapping::class);
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    public function scopeForFiscalYear($query, int $year): mixed
    {
        return $query->where('fiscal_year', $year);
    }
}

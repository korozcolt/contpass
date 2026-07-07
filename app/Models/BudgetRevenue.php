<?php

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\BudgetRevenueFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetRevenue extends Model
{
    /** @use HasFactory<BudgetRevenueFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'code',
        'name',
        'category',
        'projected_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'projected_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Total recaudado real en el rubro (suma de income_records asociados).
     */
    protected function executedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round((float) $this->incomeRecords()->sum('amount'), 2)
        );
    }

    /**
     * Porcentaje de ejecución del rubro de ingresos.
     * Retorna 0 si no hay meta proyectada.
     */
    protected function executionPercentage(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $projected = (float) $this->projected_amount;

                if ($projected <= 0) {
                    return 0.0;
                }

                return round(($this->executed_amount / $projected) * 100, 2);
            }
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function incomeRecords(): HasMany
    {
        return $this->hasMany(IncomeRecord::class);
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

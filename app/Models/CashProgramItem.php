<?php

namespace App\Models;

use App\Enums\CashProgramMovementType;
use App\Traits\Auditable;
use Carbon\CarbonImmutable;
use Database\Factories\CashProgramItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashProgramItem extends Model
{
    /** @use HasFactory<CashProgramItemFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'movement_type',
        'budget_appropriation_id',
        'budget_revenue_id',
        'month',
        'projected_amount',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => CashProgramMovementType::class,
            'fiscal_year' => 'integer',
            'month' => 'integer',
            'projected_amount' => 'decimal:2',
        ];
    }

    /**
     * Monto real ejecutado en el mes para el rubro: pagos (gasto) o recaudos (ingreso).
     */
    protected function executedAmount(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $start = CarbonImmutable::create($this->fiscal_year, $this->month, 1)->startOfMonth();
                $end = $start->endOfMonth();

                if ($this->movement_type === CashProgramMovementType::Expense) {
                    if ($this->budget_appropriation_id === null) {
                        return 0.0;
                    }

                    return round((float) Payment::query()
                        ->whereBetween('paid_on', [$start->toDateString(), $end->toDateString()])
                        ->whereHas(
                            'paymentOrder.budgetObligation.budgetRegistration.budgetAvailabilityCertificate',
                            fn ($query) => $query->where('budget_appropriation_id', $this->budget_appropriation_id)
                        )
                        ->sum('amount'), 2);
                }

                if ($this->budget_revenue_id === null) {
                    return 0.0;
                }

                return round((float) IncomeRecord::query()
                    ->where('budget_revenue_id', $this->budget_revenue_id)
                    ->whereBetween('accrual_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('amount'), 2);
            }
        );
    }

    /**
     * Desviación entre lo proyectado y lo realmente ejecutado en el mes.
     */
    protected function deviation(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round((float) $this->projected_amount - $this->executed_amount, 2)
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

    public function budgetRevenue(): BelongsTo
    {
        return $this->belongsTo(BudgetRevenue::class);
    }

    public function scopeForFiscalYear($query, int $year): mixed
    {
        return $query->where('fiscal_year', $year);
    }
}

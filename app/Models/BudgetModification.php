<?php

namespace App\Models;

use App\Enums\BudgetModificationType;
use App\Traits\Auditable;
use Database\Factories\BudgetModificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class BudgetModification extends Model
{
    /** @use HasFactory<BudgetModificationFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'document_reference',
        'source_appropriation_id',
        'destination_appropriation_id',
        'amount',
        'justification',
        'effective_date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => BudgetModificationType::class,
            'amount' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    /**
     * Model Event: al guardar una modificación presupuestal, actualizar
     * automáticamente los saldos acumulados en la(s) apropiación(es) afectada(s).
     * Se ejecuta en una transacción atómica para garantizar consistencia.
     */
    protected static function booted(): void
    {
        static::created(function (BudgetModification $modification): void {
            DB::transaction(function () use ($modification): void {
                $modification->applyToAppropriations();
            });
        });
    }

    /**
     * Aplica la modificación a los rubros presupuestales afectados.
     * - addition:  suma al campo `additions` del rubro destino.
     * - reduction: suma al campo `reductions` del rubro destino.
     * - transfer:  suma a `reductions` del rubro origen y a `additions` del destino.
     */
    public function applyToAppropriations(): void
    {
        $amount = (float) $this->amount;

        match ($this->type) {
            BudgetModificationType::Addition => $this->destinationAppropriation()
                ->getResults()
                ->increment('additions', $amount),

            BudgetModificationType::Reduction => $this->destinationAppropriation()
                ->getResults()
                ->increment('reductions', $amount),

            BudgetModificationType::Transfer => $this->applyTransfer($amount),
        };
    }

    /**
     * Aplica un traslado: debita del origen y acredita al destino.
     *
     * @throws \RuntimeException cuando el rubro origen no tiene saldo suficiente.
     */
    private function applyTransfer(float $amount): void
    {
        $source = BudgetAppropriation::findOrFail($this->source_appropriation_id);

        if ($source->available_amount < $amount) {
            throw new \RuntimeException(
                "El rubro '{$source->name}' no tiene saldo disponible suficiente para el traslado."
            );
        }

        $source->increment('reductions', $amount);
        BudgetAppropriation::findOrFail($this->destination_appropriation_id)
            ->increment('additions', $amount);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceAppropriation(): BelongsTo
    {
        return $this->belongsTo(BudgetAppropriation::class, 'source_appropriation_id');
    }

    public function destinationAppropriation(): BelongsTo
    {
        return $this->belongsTo(BudgetAppropriation::class, 'destination_appropriation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

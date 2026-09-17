<?php

namespace App\Services\Accounting;

use App\Enums\BankStatementLineStatus;
use App\Enums\BankStatementMatchConfidence;
use App\Enums\BankStatementMatchStatus;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Payment;
use Illuminate\Support\Collection;

class ProposeBankStatementMatches
{
    /**
     * Máximo de sugerencias de lote a crear por línea, para no saturar la
     * página de revisión (Plan 04) aunque existan más combinaciones válidas.
     */
    private const MAX_BATCH_SUGGESTIONS_PER_LINE = 3;

    /**
     * Tamaño máximo de un grupo de Payments dentro de una sugerencia de
     * lote (D-08).
     */
    private const MAX_BATCH_GROUP_SIZE = 5;

    public function handle(BankStatementImport $import): void
    {
        $windowDays = (int) config('contpass.bank_reconciliation.match_window_days', 3);
        $poolLimit = (int) config('contpass.bank_reconciliation.match_pool_limit', 10);

        $import->lines()
            ->where('status', BankStatementLineStatus::Pending)
            ->get()
            ->each(fn (BankStatementLine $line) => $this->proposeForLine($line, $import->cash_account_id, $windowDays, $poolLimit));
    }

    private function proposeForLine(BankStatementLine $line, int $cashAccountId, int $windowDays, int $poolLimit): void
    {
        $amountCents = (int) round(abs((float) $line->amount) * 100);
        $from = $line->line_date->subDays($windowDays);
        $to = $line->line_date->addDays($windowDays);

        // NOTA (Rule 1 - Bug del plan): `is_reconciled` es un accessor/mutator
        // virtual sobre `reconciled_at` (app/Models/Payment.php:42-47), no una
        // columna real — `where('is_reconciled', false)` fallaría contra la
        // base de datos. Se excluyen los Payments ya conciliados (is_reconciled=true)
        // filtrando por `reconciled_at` nulo, que es exactamente la condición
        // que el accessor expone como is_reconciled=false.
        $candidates = Payment::query()
            ->where('cash_account_id', $cashAccountId)
            ->whereNull('reconciled_at')
            ->whereBetween('paid_on', [$from, $to])
            ->get();

        foreach ($candidates as $payment) {
            if ((int) round((float) $payment->amount * 100) !== $amountCents) {
                continue;
            }

            $confidence = $this->referencesMatch($line->reference, $payment->reference)
                ? BankStatementMatchConfidence::High
                : BankStatementMatchConfidence::Candidate;

            $match = $line->matches()->create([
                'confidence' => $confidence,
                'status' => BankStatementMatchStatus::Proposed,
            ]);

            $match->payments()->attach($payment->id);
        }

        // Límite superior explícito del pool ANTES de generar combinaciones
        // de lote (Open Question #3 de 03-RESEARCH.md) — si el pool excede
        // match_pool_limit, esta línea solo recibe sugerencias 1:1.
        if ($candidates->count() > $poolLimit) {
            return;
        }

        if ($candidates->count() < 2) {
            return;
        }

        $this->proposeBatchMatches($line, $candidates, $amountCents);
    }

    /**
     * @param  Collection<int, Payment>  $candidates
     */
    private function proposeBatchMatches(BankStatementLine $line, Collection $candidates, int $amountCents): void
    {
        $suggested = 0;

        for ($size = 2; $size <= self::MAX_BATCH_GROUP_SIZE && $suggested < self::MAX_BATCH_SUGGESTIONS_PER_LINE; $size++) {
            foreach ($this->combinationsOfSize($candidates->all(), $size) as $combo) {
                if ($suggested >= self::MAX_BATCH_SUGGESTIONS_PER_LINE) {
                    break;
                }

                $sum = array_sum(array_map(
                    fn (Payment $payment): int => (int) round((float) $payment->amount * 100),
                    $combo
                ));

                if ($sum !== $amountCents) {
                    continue;
                }

                $match = $line->matches()->create([
                    'confidence' => BankStatementMatchConfidence::Candidate,
                    'status' => BankStatementMatchStatus::Proposed,
                ]);

                $match->payments()->attach(collect($combo)->pluck('id')->all());

                $suggested++;
            }
        }
    }

    private function referencesMatch(?string $lineReference, ?string $paymentReference): bool
    {
        if ($lineReference === null || $paymentReference === null) {
            return false;
        }

        return mb_strtolower(trim($lineReference)) === mb_strtolower(trim($paymentReference));
    }

    /**
     * Generador recursivo de combinaciones de tamaño fijo — no hay función
     * nativa de combinaciones en PHP y el pool ya está acotado por
     * match_pool_limit antes de llegar aquí (ver proposeForLine()).
     *
     * @param  array<int, Payment>  $items
     * @return array<int, array<int, Payment>>
     */
    private function combinationsOfSize(array $items, int $size): array
    {
        if ($size === 0) {
            return [[]];
        }

        if (count($items) < $size) {
            return [];
        }

        $first = array_shift($items);

        $withFirst = array_map(
            fn (array $combo): array => array_merge([$first], $combo),
            $this->combinationsOfSize($items, $size - 1)
        );

        $withoutFirst = $this->combinationsOfSize($items, $size);

        return array_merge($withFirst, $withoutFirst);
    }
}

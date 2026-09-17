<?php

namespace App\Services\Accounting;

use App\Enums\BankStatementLineStatus;
use App\Enums\BankStatementMatchStatus;
use App\Models\BankStatementMatch;
use Illuminate\Support\Facades\DB;

class ConfirmBankStatementMatch
{
    /**
     * Confirma un cruce (simple o de lote), marca cada Payment involucrado
     * como conciliado, actualiza la línea a Matched, y descarta cualquier
     * otra sugerencia propuesta para esa misma línea. Ningún cruce se
     * confirma automáticamente — esta llamada explícita es la única forma
     * de marcar Payments como conciliados desde el motor de cruce (BANKREC-05).
     */
    public function handle(BankStatementMatch $match, ?int $userId = null): void
    {
        DB::transaction(function () use ($match, $userId): void {
            foreach ($match->payments as $payment) {
                $payment->update(['is_reconciled' => true]);
            }

            $match->update([
                'status' => BankStatementMatchStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
            ]);

            $match->line->update(['status' => BankStatementLineStatus::Matched]);

            $match->line->matches()
                ->where('id', '!=', $match->id)
                ->where('status', BankStatementMatchStatus::Proposed)
                ->update(['status' => BankStatementMatchStatus::Discarded]);
        });
    }
}

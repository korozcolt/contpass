<?php

namespace App\Services\Budget;

use App\Enums\BudgetObligationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOrderStatus;
use App\Models\BudgetObligation;
use App\Models\CashAccount;
use App\Models\Company;
use App\Models\PaymentOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssuePaymentOrder
{
    /**
     * Crea una Orden de Pago a partir de una Obligación aprobada.
     * La OP nace en estado pending, pendiente de aprobación por el tesorero.
     */
    public function handle(
        Company $company,
        BudgetObligation $obligation,
        CashAccount $cashAccount,
        float $amount,
        string $method,
        string $description,
        string $issuedOn
    ): PaymentOrder {
        $amount = round($amount, 2);

        if ($obligation->status !== BudgetObligationStatus::Approved) {
            throw ValidationException::withMessages([
                'obligation' => 'Solo se pueden emitir órdenes de pago sobre obligaciones aprobadas.',
            ]);
        }

        PaymentMethod::from($method); // Valida que el método sea un valor del enum

        return DB::transaction(function () use ($company, $obligation, $cashAccount, $amount, $method, $description, $issuedOn): PaymentOrder {
            return PaymentOrder::query()->create([
                'company_id' => $company->id,
                'budget_obligation_id' => $obligation->id,
                'cash_account_id' => $cashAccount->id,
                'number' => $this->nextNumber(now()->year),
                'status' => PaymentOrderStatus::Pending,
                'amount' => $amount,
                'method' => $method,
                'issued_on' => $issuedOn,
                'description' => $description,
            ]);
        });
    }

    private function nextNumber(int $fiscalYear): string
    {
        $count = PaymentOrder::query()
            ->whereYear('issued_on', $fiscalYear)
            ->count() + 1;

        return sprintf('OP-%d-%06d', $fiscalYear, $count);
    }
}

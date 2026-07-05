<?php

namespace App\Services\Budget;

use App\Enums\BudgetObligationStatus;
use App\Models\BudgetObligation;
use App\Models\Company;
use App\Services\Accounting\PostExpenseVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveBudgetObligation
{
    public function __construct(
        private readonly PostExpenseVoucher $postExpenseVoucher,
    ) {}

    /**
     * Aprueba la obligación y dispara la causación contable automática.
     * Este es el punto exacto donde el PUC es afectado.
     * Solo el contador/admin debe poder invocar esta acción.
     */
    public function handle(Company $company, BudgetObligation $obligation): BudgetObligation
    {
        if ($obligation->status !== BudgetObligationStatus::Draft) {
            throw ValidationException::withMessages([
                'obligation' => 'Solo las obligaciones en borrador pueden ser aprobadas.',
            ]);
        }

        $obligation->load('budgetRegistration.thirdParty');
        $registration = $obligation->budgetRegistration;
        $thirdParty = $registration->thirdParty;

        // Cargar el mapeo rubro → PUC. Es obligatorio para causar.
        $mapping = $registration->budgetAvailabilityCertificate->budgetAppropriation->chartMapping;

        if ($mapping === null) {
            throw ValidationException::withMessages([
                'mapping' => 'El rubro presupuestal no tiene un mapeo de cuentas PUC configurado. Configure el mapeo antes de aprobar.',
            ]);
        }

        return DB::transaction(function () use ($company, $obligation, $thirdParty, $mapping): BudgetObligation {
            // Causar el egreso en el PUC automáticamente
            $voucher = $this->postExpenseVoucher->handle($company, $thirdParty, [
                'expense_account_id' => $mapping->expense_chart_account_id,
                'payable_account_id' => $mapping->payable_chart_account_id,
                'support_type' => $obligation->support_type,
                'support_number' => $obligation->support_number,
                'accrual_date' => $obligation->accrual_date->toDateString(),
                'amount' => (float) $obligation->amount,
                'has_valid_support' => true,
                'is_deductible' => true,
                'description' => $obligation->description ?? "Obligación {$obligation->number}",
            ], $obligation);

            $obligation->forceFill([
                'status' => BudgetObligationStatus::Approved,
                'voucher_id' => $voucher->id,
                'approved_at' => now(),
            ])->save();

            return $obligation->refresh();
        });
    }
}

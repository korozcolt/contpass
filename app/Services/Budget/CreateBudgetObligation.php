<?php

namespace App\Services\Budget;

use App\Enums\BudgetObligationStatus;
use App\Enums\BudgetRegistrationStatus;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBudgetObligation
{
    /**
     * Crea una Obligación en estado draft sobre un RP.
     * NO dispara PostExpenseVoucher. El PUC no es afectado aquí.
     * La causación ocurre solo al aprobar la obligación (ApproveBudgetObligation).
     */
    public function handle(
        Company $company,
        BudgetRegistration $registration,
        float $amount,
        string $supportType,
        string $supportNumber,
        string $accrualDate,
        ?string $description = null
    ): BudgetObligation {
        $amount = round($amount, 2);

        if ($registration->status !== BudgetRegistrationStatus::Active) {
            throw ValidationException::withMessages([
                'registration' => 'El Registro Presupuestal seleccionado no está activo.',
            ]);
        }

        if ($amount > $registration->available_for_obligation) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'El monto $%s supera el saldo disponible del RP ($%s).',
                    number_format($amount, 2),
                    number_format($registration->available_for_obligation, 2)
                ),
            ]);
        }

        return DB::transaction(function () use ($company, $registration, $amount, $supportType, $supportNumber, $accrualDate, $description): BudgetObligation {
            $obligation = BudgetObligation::query()->create([
                'company_id' => $company->id,
                'budget_registration_id' => $registration->id,
                'number' => $this->nextNumber($registration->fiscal_year),
                'status' => BudgetObligationStatus::Draft,
                'fiscal_year' => $registration->fiscal_year,
                'amount' => $amount,
                'support_type' => $supportType,
                'support_number' => $supportNumber,
                'accrual_date' => $accrualDate,
                'description' => $description,
            ]);

            // Si el RP se agotó, marcarlo como totalmente obligado
            $remainingBalance = $registration->refresh()->available_for_obligation;
            if ($remainingBalance <= 0) {
                $registration->forceFill(['status' => BudgetRegistrationStatus::FullyObligated])->save();
            }

            return $obligation;
        });
    }

    private function nextNumber(int $fiscalYear): string
    {
        $count = BudgetObligation::query()
            ->where('fiscal_year', $fiscalYear)
            ->count() + 1;

        return sprintf('OBL-%d-%06d', $fiscalYear, $count);
    }
}

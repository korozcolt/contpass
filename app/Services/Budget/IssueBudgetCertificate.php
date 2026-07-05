<?php

namespace App\Services\Budget;

use App\Enums\BudgetCertificateStatus;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueBudgetCertificate
{
    /**
     * Emite un CDP sobre un rubro presupuestal.
     * Valida que el monto no exceda el saldo disponible de la apropiación.
     */
    public function handle(
        Company $company,
        BudgetAppropriation $appropriation,
        float $amount,
        string $justification,
        string $issuedOn,
        ?string $expiresOn = null
    ): BudgetAvailabilityCertificate {
        $amount = round($amount, 2);

        if ($amount > $appropriation->available_amount) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'El monto $%s supera el saldo disponible del rubro ($%s).',
                    number_format($amount, 2),
                    number_format($appropriation->available_amount, 2)
                ),
            ]);
        }

        return DB::transaction(function () use ($company, $appropriation, $amount, $justification, $issuedOn, $expiresOn): BudgetAvailabilityCertificate {
            $number = $this->nextNumber($appropriation->fiscal_year);

            return BudgetAvailabilityCertificate::query()->create([
                'company_id' => $company->id,
                'budget_appropriation_id' => $appropriation->id,
                'number' => $number,
                'status' => BudgetCertificateStatus::Active,
                'fiscal_year' => $appropriation->fiscal_year,
                'amount' => $amount,
                'justification' => $justification,
                'issued_on' => $issuedOn,
                'expires_on' => $expiresOn,
            ]);
        });
    }

    private function nextNumber(int $fiscalYear): string
    {
        $count = BudgetAvailabilityCertificate::query()
            ->where('fiscal_year', $fiscalYear)
            ->count() + 1;

        return sprintf('CDP-%d-%06d', $fiscalYear, $count);
    }
}

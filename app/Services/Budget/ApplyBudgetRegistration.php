<?php

namespace App\Services\Budget;

use App\Enums\BudgetCertificateStatus;
use App\Enums\BudgetRegistrationStatus;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetRegistration;
use App\Models\Company;
use App\Models\ThirdParty;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyBudgetRegistration
{
    /**
     * Convierte un CDP en Registro Presupuestal (RP), vinculando un tercero.
     * Valida que el CDP esté activo y que haya saldo suficiente en él.
     */
    public function handle(
        Company $company,
        BudgetAvailabilityCertificate $certificate,
        ThirdParty $thirdParty,
        float $amount,
        string $justification,
        string $issuedOn
    ): BudgetRegistration {
        $amount = round($amount, 2);

        if ($certificate->status !== BudgetCertificateStatus::Active) {
            throw ValidationException::withMessages([
                'certificate' => 'El CDP seleccionado no está activo.',
            ]);
        }

        if ($amount > $certificate->available_for_registration) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'El monto $%s supera el saldo disponible del CDP ($%s).',
                    number_format($amount, 2),
                    number_format($certificate->available_for_registration, 2)
                ),
            ]);
        }

        return DB::transaction(function () use ($company, $certificate, $thirdParty, $amount, $justification, $issuedOn): BudgetRegistration {
            $registration = BudgetRegistration::query()->create([
                'company_id' => $company->id,
                'budget_availability_certificate_id' => $certificate->id,
                'third_party_id' => $thirdParty->id,
                'number' => $this->nextNumber($certificate->fiscal_year),
                'status' => BudgetRegistrationStatus::Active,
                'fiscal_year' => $certificate->fiscal_year,
                'amount' => $amount,
                'justification' => $justification,
                'issued_on' => $issuedOn,
            ]);

            // Si el CDP se agotó, marcarlo como comprometido
            $remainingBalance = $certificate->refresh()->available_for_registration;
            if ($remainingBalance <= 0) {
                $certificate->forceFill(['status' => BudgetCertificateStatus::FullyCommitted])->save();
            }

            return $registration;
        });
    }

    private function nextNumber(int $fiscalYear): string
    {
        $count = BudgetRegistration::query()
            ->where('fiscal_year', $fiscalYear)
            ->count() + 1;

        return sprintf('RP-%d-%06d', $fiscalYear, $count);
    }
}

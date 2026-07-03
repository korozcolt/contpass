<?php

namespace App\Services\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Company;
use Illuminate\Validation\ValidationException;

class EnsureOpenAccountingPeriod
{
    public function handle(Company $company, string $date): void
    {
        $closedPeriod = AccountingPeriod::query()
            ->whereBelongsTo($company)
            ->where('starts_on', '<=', $date)
            ->where('ends_on', '>=', $date)
            ->where('is_closed', true)
            ->exists();

        if ($closedPeriod) {
            throw ValidationException::withMessages([
                'date' => 'El periodo contable de la fecha seleccionada está cerrado. Cree una nota de ajuste.',
            ]);
        }
    }
}

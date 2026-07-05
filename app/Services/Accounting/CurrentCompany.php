<?php

namespace App\Services\Accounting;

use App\Models\Company;

class CurrentCompany
{
    public function get(): Company
    {
        $nit = config('contpass.company_nit');

        if ($nit) {
            return Company::query()->where('tax_id', $nit)->firstOrFail();
        }

        return Company::query()->oldest()->firstOr(fn () => Company::query()->create([
            'tax_id' => '900000000',
            'name' => 'Empresa Principal',
            'verification_digit' => 9,
            'currency' => 'COP',
        ]));
    }
}

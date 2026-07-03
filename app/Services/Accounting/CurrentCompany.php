<?php

namespace App\Services\Accounting;

use App\Models\Company;

class CurrentCompany
{
    public function get(): Company
    {
        return Company::query()->firstOrCreate(
            ['tax_id' => '900000000'],
            [
                'name' => 'Empresa Principal',
                'verification_digit' => 9,
                'currency' => 'COP',
            ],
        );
    }
}

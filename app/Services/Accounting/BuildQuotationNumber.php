<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class BuildQuotationNumber
{
    public function next(Company $company): string
    {
        return DB::transaction(function () use ($company): string {
            $year = now()->format('Y');

            $count = Quotation::query()
                ->whereBelongsTo($company)
                ->where('number', 'like', "COT-{$year}-%")
                ->lockForUpdate()
                ->count();

            return sprintf('COT-%s-%05d', $year, $count + 1);
        });
    }
}

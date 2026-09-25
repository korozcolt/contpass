<?php

namespace App\Services\Accounting;

use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\Voucher;

class BuildVoucherNumber
{
    public function next(VoucherType $type, ?Company $company = null, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = $type->prefix();

        $query = Voucher::query()
            ->where('type', $type->value);

        $pattern = "{$prefix}-{$year}-%";
        $last = (clone $query)
            ->where('number', 'like', $pattern)
            ->orderByDesc('id')
            ->value('number');

        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        } else {
            $sequence = $query->count() + 1;
        }

        return sprintf('%s-%d-%06d', $prefix, $year, $sequence);
    }
}

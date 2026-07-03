<?php

namespace App\Services\Accounting;

use App\Enums\VoucherType;
use App\Models\Voucher;

class BuildVoucherNumber
{
    public function next(VoucherType $type): string
    {
        $prefix = strtoupper(substr($type->value, 0, 3));
        $next = Voucher::query()->where('type', $type->value)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $next);
    }
}

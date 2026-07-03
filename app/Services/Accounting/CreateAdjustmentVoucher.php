<?php

namespace App\Services\Accounting;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class CreateAdjustmentVoucher
{
    public function __construct(private readonly PostsBalancedVoucher $postsBalancedVoucher) {}

    /**
     * @param  array<int, array{chart_account_id: int, third_party_id?: int|null, description: string, debit?: float, credit?: float}>  $entries
     */
    public function handle(Company $company, Voucher $adjustedVoucher, string $date, string $description, array $entries): Voucher
    {
        return DB::transaction(function () use ($company, $adjustedVoucher, $date, $description, $entries): Voucher {
            $voucher = $this->postsBalancedVoucher->handle($company, VoucherType::Adjustment, $date, $description, $entries, $adjustedVoucher->thirdParty, $adjustedVoucher);

            $adjustedVoucher->forceFill(['status' => VoucherStatus::Adjusted])->save();

            return $voucher;
        });
    }
}

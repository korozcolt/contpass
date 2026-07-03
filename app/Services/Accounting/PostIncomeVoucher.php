<?php

namespace App\Services\Accounting;

use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\IncomeRecord;
use App\Models\ThirdParty;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class PostIncomeVoucher
{
    public function __construct(private readonly PostsBalancedVoucher $postsBalancedVoucher) {}

    /**
     * @param  array{third_party_id: int, revenue_account_id: int, receivable_account_id: int, support_number: string, accrual_date: string, amount: numeric-string|float|int, description?: string}  $data
     */
    public function handle(Company $company, ThirdParty $thirdParty, array $data): Voucher
    {
        return DB::transaction(function () use ($company, $thirdParty, $data): Voucher {
            $amount = round((float) $data['amount'], 2);
            $description = $data['description'] ?? "Ingreso {$data['support_number']}";

            $voucher = $this->postsBalancedVoucher->handle($company, VoucherType::Income, $data['accrual_date'], $description, [
                [
                    'chart_account_id' => (int) $data['receivable_account_id'],
                    'description' => $description,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'chart_account_id' => (int) $data['revenue_account_id'],
                    'description' => $description,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ], $thirdParty);

            IncomeRecord::query()->create([
                'voucher_id' => $voucher->id,
                'revenue_account_id' => $data['revenue_account_id'],
                'receivable_account_id' => $data['receivable_account_id'],
                'support_number' => $data['support_number'],
                'accrual_date' => $data['accrual_date'],
                'amount' => $amount,
            ]);

            return $voucher;
        });
    }
}

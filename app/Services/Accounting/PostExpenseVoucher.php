<?php

namespace App\Services\Accounting;

use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\ExpenseRecord;
use App\Models\ThirdParty;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class PostExpenseVoucher
{
    public function __construct(
        private readonly ApplyWithholdingRules $applyWithholdingRules,
        private readonly PostsBalancedVoucher $postsBalancedVoucher,
    ) {}

    /**
     * @param  array{third_party_id: int, expense_account_id: int, payable_account_id: int, support_type: string, support_number: string, accrual_date: string, amount: numeric-string|float|int, has_valid_support?: bool, is_deductible?: bool, description?: string}  $data
     */
    public function handle(Company $company, ThirdParty $thirdParty, array $data): Voucher
    {
        return DB::transaction(function () use ($company, $thirdParty, $data): Voucher {
            $amount = round((float) $data['amount'], 2);
            $description = $data['description'] ?? "Egreso {$data['support_number']}";
            $withholdings = $this->applyWithholdingRules->handle($company, $amount, $data['accrual_date']);
            $withholdingAmount = round($withholdings->sum('amount'), 2);
            $payableAmount = round($amount - $withholdingAmount, 2);

            $entries = [
                [
                    'chart_account_id' => (int) $data['expense_account_id'],
                    'description' => $description,
                    'debit' => $amount,
                    'credit' => 0,
                ],
            ];

            foreach ($withholdings as $withholding) {
                $entries[] = [
                    'chart_account_id' => $withholding['rule']->chart_account_id,
                    'description' => "Retención {$withholding['rule']->concept}",
                    'debit' => 0,
                    'credit' => $withholding['amount'],
                ];
            }

            $entries[] = [
                'chart_account_id' => (int) $data['payable_account_id'],
                'description' => $description,
                'debit' => 0,
                'credit' => $payableAmount,
            ];

            $voucher = $this->postsBalancedVoucher->handle($company, VoucherType::Expense, $data['accrual_date'], $description, $entries, $thirdParty);

            ExpenseRecord::query()->create([
                'voucher_id' => $voucher->id,
                'expense_account_id' => $data['expense_account_id'],
                'payable_account_id' => $data['payable_account_id'],
                'support_type' => $data['support_type'],
                'support_number' => $data['support_number'],
                'accrual_date' => $data['accrual_date'],
                'amount' => $amount,
                'withholding_amount' => $withholdingAmount,
                'has_valid_support' => (bool) ($data['has_valid_support'] ?? false),
                'is_deductible' => (bool) ($data['is_deductible'] ?? true),
            ]);

            return $voucher;
        });
    }
}

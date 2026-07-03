<?php

namespace App\Services\Accounting;

use App\Enums\PaymentMethod;
use App\Enums\VoucherType;
use App\Models\CashAccount;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class RegisterPayment
{
    public function __construct(private readonly PostsBalancedVoucher $postsBalancedVoucher) {}

    /**
     * @param  array{source_voucher_id?: int|null, cash_account_id: int, counterparty_account_id: int, third_party_id?: int|null, method: string, reference?: string|null, paid_on: string, amount: numeric-string|float|int, description?: string}  $data
     */
    public function handle(Company $company, CashAccount $cashAccount, array $data, ?Voucher $sourceVoucher = null): Voucher
    {
        return DB::transaction(function () use ($company, $cashAccount, $data, $sourceVoucher): Voucher {
            $amount = round((float) $data['amount'], 2);
            $method = PaymentMethod::from($data['method']);
            $description = $data['description'] ?? 'Pago registrado';
            $isIncomeCollection = $sourceVoucher?->type === VoucherType::Income;

            $entries = $isIncomeCollection ? [
                [
                    'chart_account_id' => $cashAccount->chart_account_id,
                    'description' => $description,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'chart_account_id' => (int) $data['counterparty_account_id'],
                    'description' => $description,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ] : [
                [
                    'chart_account_id' => (int) $data['counterparty_account_id'],
                    'description' => $description,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'chart_account_id' => $cashAccount->chart_account_id,
                    'description' => $description,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ];

            $voucher = $this->postsBalancedVoucher->handle($company, VoucherType::Payment, $data['paid_on'], $description, $entries, $sourceVoucher?->thirdParty);

            Payment::query()->create([
                'voucher_id' => $voucher->id,
                'source_voucher_id' => $sourceVoucher?->id,
                'cash_account_id' => $cashAccount->id,
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'paid_on' => $data['paid_on'],
                'amount' => $amount,
                'is_bancarized' => $method->isBancarized(),
            ]);

            return $voucher;
        });
    }
}

<?php

namespace App\Services\Accounting;

use App\Enums\VoucherStatus;
use App\Models\AccountingEntry;
use App\Models\CashAccount;
use App\Models\Company;
use App\Models\Payment;
use Illuminate\Support\Collection;

class BankReconciliation
{
    /**
     * @return array{book_balance: float, reconciled_balance: float, pending_balance: float}
     */
    public function summary(Company $company, CashAccount $cashAccount, ?string $cutoff = null): array
    {
        $bookBalance = $this->bookBalance($company, $cashAccount, $cutoff);
        $reconciledBalance = round((float) $this->payments($company, $cashAccount, $cutoff)
            ->filter(fn (array $row): bool => $row['reconciled'])
            ->sum('signed_amount'), 2);

        return [
            'book_balance' => $bookBalance,
            'reconciled_balance' => $reconciledBalance,
            'pending_balance' => round($bookBalance - $reconciledBalance, 2),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function pendingItems(Company $company, CashAccount $cashAccount, ?string $cutoff = null): Collection
    {
        return $this->payments($company, $cashAccount, $cutoff)
            ->reject(fn (array $row): bool => $row['reconciled'])
            ->values();
    }

    private function bookBalance(Company $company, CashAccount $cashAccount, ?string $cutoff): float
    {
        $totals = AccountingEntry::query()
            ->where('chart_account_id', $cashAccount->chart_account_id)
            ->whereHas('voucher', fn ($query) => $query
                ->whereBelongsTo($company)
                ->where('status', '!=', VoucherStatus::Void->value)
                ->when($cutoff, fn ($query, string $date) => $query->whereDate('date', '<=', $date)))
            ->selectRaw('sum(debit) as debit_total, sum(credit) as credit_total')
            ->first();

        return round((float) ($totals->debit_total ?? 0) - (float) ($totals->credit_total ?? 0), 2);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function payments(Company $company, CashAccount $cashAccount, ?string $cutoff): Collection
    {
        return Payment::query()
            ->where('cash_account_id', $cashAccount->id)
            ->with(['voucher.thirdParty'])
            ->whereHas('voucher', fn ($query) => $query
                ->whereBelongsTo($company)
                ->where('status', '!=', VoucherStatus::Void->value)
                ->when($cutoff, fn ($query, string $date) => $query->whereDate('date', '<=', $date)))
            ->orderBy('paid_on')
            ->get()
            ->map(function (Payment $payment) use ($cashAccount): array {
                $entry = AccountingEntry::query()
                    ->where('voucher_id', $payment->voucher_id)
                    ->where('chart_account_id', $cashAccount->chart_account_id)
                    ->first();

                return [
                    'id' => (string) $payment->id,
                    'date' => $payment->paid_on,
                    'voucher_number' => $payment->voucher->number,
                    'third_party' => $payment->voucher->thirdParty?->name,
                    'reference' => $payment->reference,
                    'signed_amount' => $entry ? round((float) $entry->debit - (float) $entry->credit, 2) : 0.0,
                    'reconciled' => $payment->is_reconciled,
                ];
            });
    }
}

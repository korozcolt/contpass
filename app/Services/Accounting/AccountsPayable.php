<?php

namespace App\Services\Accounting;

use App\Enums\BudgetObligationStatus;
use App\Models\BudgetObligation;
use App\Models\Company;
use App\Models\ExpenseRecord;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountsPayable
{
    /**
     * Open (unpaid or partially paid) accounts payable for a company — combines public budget
     * obligations (payment_order_id) with private-market expense records (source_voucher_id, AP-01),
     * aged by their accrual date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openItems(Company $company): Collection
    {
        return $this->budgetObligationItems($company)
            ->concat($this->privateMarketItems($company))
            ->filter(fn (array $row): bool => $row['pending'] > 0.01)
            ->values();
    }

    private function budgetObligationItems(Company $company): Collection
    {
        $obligations = BudgetObligation::query()
            ->with(['budgetRegistration.thirdParty', 'paymentOrder'])
            ->whereBelongsTo($company)
            ->where('status', '!=', BudgetObligationStatus::Cancelled->value)
            ->get();

        $paymentOrderIds = $obligations->pluck('paymentOrder.id')->filter();

        $paidByPaymentOrder = Payment::query()
            ->whereIn('payment_order_id', $paymentOrderIds)
            ->selectRaw('payment_order_id, sum(amount) as paid_total')
            ->groupBy('payment_order_id')
            ->pluck('paid_total', 'payment_order_id');

        return $obligations->map(function (BudgetObligation $obligation) use ($paidByPaymentOrder): array {
            $paid = (float) ($paidByPaymentOrder[$obligation->paymentOrder?->id] ?? 0);
            $pending = round((float) $obligation->amount - $paid, 2);
            $daysOverdue = (int) $obligation->accrual_date->diffInDays(now(), absolute: true);

            return [
                'budget_obligation_id' => $obligation->id,
                'third_party' => $obligation->budgetRegistration->thirdParty?->name ?? 'Sin tercero',
                'number' => $obligation->number,
                'support_number' => $obligation->support_number,
                'accrual_date' => $obligation->accrual_date,
                'amount' => (float) $obligation->amount,
                'paid' => $paid,
                'pending' => $pending,
                'days_overdue' => $daysOverdue,
                'bucket' => $this->bucket($daysOverdue),
            ];
        });
    }

    private function privateMarketItems(Company $company): Collection
    {
        $records = ExpenseRecord::query()
            ->with(['voucher.thirdParty'])
            ->whereNull('budget_obligation_id')
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo($company))
            ->get();

        $paidByVoucher = Payment::query()
            ->whereIn('source_voucher_id', $records->pluck('voucher_id'))
            ->selectRaw('source_voucher_id, sum(amount) as paid_total')
            ->groupBy('source_voucher_id')
            ->pluck('paid_total', 'source_voucher_id');

        return $records->map(function (ExpenseRecord $expense) use ($paidByVoucher): array {
            $paid = (float) ($paidByVoucher[$expense->voucher_id] ?? 0);
            $netAmount = round((float) $expense->amount - (float) $expense->withholding_amount, 2);
            $pending = round($netAmount - $paid, 2);
            $daysOverdue = (int) $expense->accrual_date->diffInDays(now(), absolute: true);

            return [
                'budget_obligation_id' => null,
                'third_party' => $expense->voucher->thirdParty?->name ?? 'Sin tercero',
                'number' => $expense->voucher->number,
                'support_number' => $expense->support_number,
                'accrual_date' => $expense->accrual_date,
                'amount' => $netAmount,
                'paid' => $paid,
                'pending' => $pending,
                'days_overdue' => $daysOverdue,
                'bucket' => $this->bucket($daysOverdue),
            ];
        });
    }

    private function bucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 30 => 'Corriente',
            $daysOverdue <= 60 => '31-60 días',
            $daysOverdue <= 90 => '61-90 días',
            default => '+90 días',
        };
    }
}

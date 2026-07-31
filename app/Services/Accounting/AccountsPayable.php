<?php

namespace App\Services\Accounting;

use App\Enums\BudgetObligationStatus;
use App\Models\BudgetObligation;
use App\Models\Company;
use App\Models\Payment;
use Illuminate\Support\Collection;

class AccountsPayable
{
    /**
     * Open (unpaid or partially paid) budget obligations for a company, aged by their accrual date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openItems(Company $company): Collection
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

        return $obligations
            ->map(function (BudgetObligation $obligation) use ($paidByPaymentOrder): array {
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
            })
            ->filter(fn (array $row): bool => $row['pending'] > 0.01)
            ->values();
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

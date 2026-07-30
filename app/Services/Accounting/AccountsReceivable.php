<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\IncomeRecord;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountsReceivable
{
    /**
     * Open (unpaid or partially paid) income records for a company, aged by their accrual date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openItems(Company $company): Collection
    {
        $records = IncomeRecord::query()
            ->with(['voucher.thirdParty'])
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo($company))
            ->get();

        $paidByVoucher = Payment::query()
            ->whereIn('source_voucher_id', $records->pluck('voucher_id'))
            ->selectRaw('source_voucher_id, sum(amount) as paid_total')
            ->groupBy('source_voucher_id')
            ->pluck('paid_total', 'source_voucher_id');

        return $records
            ->map(function (IncomeRecord $income) use ($paidByVoucher): array {
                $paid = (float) ($paidByVoucher[$income->voucher_id] ?? 0);
                $pending = round((float) $income->amount - $paid, 2);
                $daysOverdue = (int) $income->accrual_date->diffInDays(now(), absolute: true);

                return [
                    'income_record_id' => $income->id,
                    'third_party' => $income->voucher->thirdParty?->name ?? 'Sin tercero',
                    'voucher_number' => $income->voucher->number,
                    'support_number' => $income->support_number,
                    'accrual_date' => $income->accrual_date,
                    'amount' => (float) $income->amount,
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

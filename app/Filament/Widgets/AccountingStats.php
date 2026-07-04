<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\ExpenseRecord;
use App\Models\IncomeRecord;
use App\Models\Payment;
use App\Services\Accounting\CurrentCompany;
use Carbon\CarbonInterface;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class AccountingStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $company = app(CurrentCompany::class)->get();
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $currentIncome = $this->incomeTotal($company, $currentMonthStart, $currentMonthEnd);
        $previousIncome = $this->incomeTotal($company, $previousMonthStart, $previousMonthEnd);
        $currentExpenses = $this->expenseTotal($company, $currentMonthStart, $currentMonthEnd);
        $previousExpenses = $this->expenseTotal($company, $previousMonthStart, $previousMonthEnd);
        $netResult = $currentIncome - $currentExpenses;
        $nonBancarizedPayments = $this->nonBancarizedPaymentCount($company, $currentMonthStart, $currentMonthEnd);
        $nonBancarizedAmount = $this->nonBancarizedPaymentTotal($company, $currentMonthStart, $currentMonthEnd);

        return [
            Stat::make('Ingresos del mes', $this->money($currentIncome))
                ->description($this->trendDescription($currentIncome, $previousIncome))
                ->descriptionIcon($this->trendIcon($currentIncome, $previousIncome, true))
                ->color($this->trendColor($currentIncome, $previousIncome, true))
                ->icon(Heroicon::OutlinedBanknotes)
                ->chart($this->monthlyIncomeSeries($company)),
            Stat::make('Egresos del mes', $this->money($currentExpenses))
                ->description($this->trendDescription($currentExpenses, $previousExpenses))
                ->descriptionIcon($this->trendIcon($currentExpenses, $previousExpenses, false))
                ->color($this->trendColor($currentExpenses, $previousExpenses, false))
                ->icon(Heroicon::OutlinedReceiptRefund)
                ->chart($this->monthlyExpenseSeries($company)),
            Stat::make('Resultado operativo', $this->money($netResult))
                ->description($netResult >= 0 ? 'Ingresos menos egresos causados' : 'Egresos por encima de ingresos')
                ->descriptionIcon($netResult >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown)
                ->color($netResult >= 0 ? 'success' : 'danger')
                ->icon(Heroicon::OutlinedScale)
                ->chart($this->monthlyNetSeries($company)),
            Stat::make('Pagos no bancarizados', Number::format($nonBancarizedPayments, locale: 'es_CO'))
                ->description($nonBancarizedPayments > 0 ? $this->money($nonBancarizedAmount).' por revisar' : 'Sin alertas este mes')
                ->descriptionIcon($nonBancarizedPayments > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedShieldCheck)
                ->color($nonBancarizedPayments > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedShieldExclamation),
        ];
    }

    private function incomeTotal(Company $company, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) IncomeRecord::query()
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo($company))
            ->whereBetween('accrual_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
    }

    private function expenseTotal(Company $company, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) ExpenseRecord::query()
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo($company))
            ->whereBetween('accrual_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
    }

    private function nonBancarizedPaymentCount(Company $company, CarbonInterface $startDate, CarbonInterface $endDate): int
    {
        return Payment::query()
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo($company))
            ->whereBetween('paid_on', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_bancarized', false)
            ->count();
    }

    private function nonBancarizedPaymentTotal(Company $company, CarbonInterface $startDate, CarbonInterface $endDate): float
    {
        return (float) Payment::query()
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo($company))
            ->whereBetween('paid_on', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_bancarized', false)
            ->sum('amount');
    }

    /**
     * @return array<float>
     */
    private function monthlyIncomeSeries(Company $company): array
    {
        return $this->monthlySeries(fn (CarbonInterface $startDate, CarbonInterface $endDate): float => $this->incomeTotal($company, $startDate, $endDate));
    }

    /**
     * @return array<float>
     */
    private function monthlyExpenseSeries(Company $company): array
    {
        return $this->monthlySeries(fn (CarbonInterface $startDate, CarbonInterface $endDate): float => $this->expenseTotal($company, $startDate, $endDate));
    }

    /**
     * @return array<float>
     */
    private function monthlyNetSeries(Company $company): array
    {
        return $this->monthlySeries(function (CarbonInterface $startDate, CarbonInterface $endDate) use ($company): float {
            return $this->incomeTotal($company, $startDate, $endDate) - $this->expenseTotal($company, $startDate, $endDate);
        });
    }

    /**
     * @param  callable(CarbonInterface, CarbonInterface): float  $resolver
     * @return array<float>
     */
    private function monthlySeries(callable $resolver): array
    {
        $series = [];

        foreach (range(5, 0) as $monthsAgo) {
            $startDate = now()->subMonthsNoOverflow($monthsAgo)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $series[] = round($resolver($startDate, $endDate), 2);
        }

        return $series;
    }

    private function trendDescription(float $currentValue, float $previousValue): string
    {
        if (round($previousValue, 2) === 0.0) {
            return round($currentValue, 2) === 0.0
                ? 'Sin movimiento mensual'
                : 'Sin base comparable del mes anterior';
        }

        $variation = (($currentValue - $previousValue) / abs($previousValue)) * 100;

        return Number::percentage($variation, precision: 1, locale: 'es_CO').' vs mes anterior';
    }

    private function trendIcon(float $currentValue, float $previousValue, bool $higherIsBetter): Heroicon
    {
        if (round($currentValue, 2) === round($previousValue, 2)) {
            return Heroicon::OutlinedMinus;
        }

        $isHigher = $currentValue > $previousValue;

        return $isHigher === $higherIsBetter
            ? Heroicon::OutlinedArrowTrendingUp
            : Heroicon::OutlinedArrowTrendingDown;
    }

    private function trendColor(float $currentValue, float $previousValue, bool $higherIsBetter): string
    {
        if (round($currentValue, 2) === round($previousValue, 2)) {
            return 'gray';
        }

        return ($currentValue > $previousValue) === $higherIsBetter ? 'success' : 'warning';
    }

    private function money(float $amount): string
    {
        return 'COP '.Number::currency($amount, 'COP', 'es_CO');
    }
}

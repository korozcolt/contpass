<?php

namespace App\Services\Accounting;

use App\Enums\VoucherStatus;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialStatement
{
    /**
     * @var array<string, array{label: string, nature: string}>
     */
    private const CLASSES = [
        '1' => ['label' => 'Activo', 'nature' => 'debit'],
        '2' => ['label' => 'Pasivo', 'nature' => 'credit'],
        '3' => ['label' => 'Patrimonio', 'nature' => 'credit'],
        '4' => ['label' => 'Ingresos', 'nature' => 'credit'],
        '5' => ['label' => 'Gastos', 'nature' => 'debit'],
        '6' => ['label' => 'Costos de Venta', 'nature' => 'debit'],
        '7' => ['label' => 'Costos de Producción', 'nature' => 'debit'],
    ];

    /**
     * @return array{classes: Collection<int, array<string, mixed>>, totals: array<string, float>, net_income: float, is_balanced: bool}
     */
    public function balanceSheet(Company $company, ?string $cutoff = null): array
    {
        $classes = $this->classifiedBalances($company, ['1', '2', '3'], null, $cutoff);
        $netIncome = $this->netIncome($company, null, $cutoff);

        $totals = [
            'activo' => (float) ($classes->firstWhere('class', '1')['total'] ?? 0),
            'pasivo' => (float) ($classes->firstWhere('class', '2')['total'] ?? 0),
            'patrimonio' => (float) ($classes->firstWhere('class', '3')['total'] ?? 0),
        ];

        $isBalanced = round($totals['activo'] - ($totals['pasivo'] + $totals['patrimonio'] + $netIncome), 2) === 0.0;

        return [
            'classes' => $classes,
            'totals' => $totals,
            'net_income' => $netIncome,
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * @return array{classes: Collection<int, array<string, mixed>>, totals: array<string, float>, net_income: float}
     */
    public function incomeStatement(Company $company, ?string $starts = null, ?string $ends = null): array
    {
        $classes = $this->classifiedBalances($company, ['4', '5', '6', '7'], $starts, $ends);

        $revenue = (float) ($classes->firstWhere('class', '4')['total'] ?? 0);
        $expenses = $classes->whereIn('class', ['5', '6', '7'])->sum('total');

        return [
            'classes' => $classes,
            'totals' => [
                'ingresos' => $revenue,
                'gastos_costos' => $expenses,
            ],
            'net_income' => round($revenue - $expenses, 2),
        ];
    }

    /**
     * Flat, table-ready rows (one per account) for the balance sheet classes (Activo/Pasivo/Patrimonio).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function balanceSheetAccounts(Company $company, ?string $cutoff = null): Collection
    {
        return $this->flattenAccounts($this->balanceSheet($company, $cutoff)['classes']);
    }

    /**
     * Flat, table-ready rows (one per account) for the income statement classes (Ingresos/Gastos/Costos).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function incomeStatementAccounts(Company $company, ?string $starts = null, ?string $ends = null): Collection
    {
        return $this->flattenAccounts($this->incomeStatement($company, $starts, $ends)['classes']);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $classes
     * @return Collection<int, array<string, mixed>>
     */
    private function flattenAccounts(Collection $classes): Collection
    {
        return $classes
            ->flatMap(fn (array $class): Collection => $class['accounts']->map(fn (array $account): array => [
                'class_label' => $class['label'],
                'code' => $account['code'],
                'name' => $account['name'],
                'balance' => $account['balance'],
            ]))
            ->values()
            ->map(fn (array $row, int $index): array => ['id' => (string) ($index + 1), ...$row]);
    }

    private function netIncome(Company $company, ?string $starts, ?string $ends): float
    {
        return $this->incomeStatement($company, $starts, $ends)['net_income'];
    }

    /**
     * @param  array<int, string>  $classDigits
     * @return Collection<int, array<string, mixed>>
     */
    private function classifiedBalances(Company $company, array $classDigits, ?string $starts, ?string $ends): Collection
    {
        $accounts = DB::table('accounting_entries')
            ->select(
                'chart_accounts.code',
                'chart_accounts.name',
                DB::raw('sum(accounting_entries.debit) as debit_total'),
                DB::raw('sum(accounting_entries.credit) as credit_total'),
            )
            ->join('chart_accounts', 'chart_accounts.id', '=', 'accounting_entries.chart_account_id')
            ->join('vouchers', 'vouchers.id', '=', 'accounting_entries.voucher_id')
            ->where('vouchers.company_id', $company->id)
            ->where('vouchers.status', '!=', VoucherStatus::Void->value)
            ->when($starts, fn ($query, $date) => $query->whereDate('vouchers.date', '>=', $date))
            ->when($ends, fn ($query, $date) => $query->whereDate('vouchers.date', '<=', $date))
            ->groupBy('chart_accounts.id', 'chart_accounts.code', 'chart_accounts.name')
            ->orderBy('chart_accounts.code')
            ->get()
            ->filter(fn (object $row): bool => in_array(substr((string) $row->code, 0, 1), $classDigits, true));

        return collect($classDigits)->map(function (string $digit) use ($accounts): array {
            $meta = self::CLASSES[$digit];

            $rows = $accounts
                ->filter(fn (object $row): bool => substr((string) $row->code, 0, 1) === $digit)
                ->map(fn (object $row): array => [
                    'code' => $row->code,
                    'name' => $row->name,
                    'balance' => $this->signedBalance($meta['nature'], (float) $row->debit_total, (float) $row->credit_total),
                ])
                ->values();

            return [
                'class' => $digit,
                'label' => $meta['label'],
                'accounts' => $rows,
                'total' => round((float) $rows->sum('balance'), 2),
            ];
        });
    }

    private function signedBalance(string $nature, float $debit, float $credit): float
    {
        return round($nature === 'debit' ? $debit - $credit : $credit - $debit, 2);
    }
}

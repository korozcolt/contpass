<?php

namespace App\Http\Controllers;

use App\Models\AccountingEntry;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\ThirdParty;
use App\Services\Accounting\AccountsReceivable;
use App\Services\Accounting\BankReconciliation;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\FinancialStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingReportController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function ledger(Request $request): View|StreamedResponse
    {
        $company = $this->currentCompany->get();
        $entries = $this->ledgerQuery($request);

        if ($request->boolean('export')) {
            return $this->downloadCsv('libro-auxiliar.csv', ['Fecha', 'Comprobante', 'Cuenta', 'Tercero', 'Descripción', 'Débito', 'Crédito'], $entries->get()->map(fn (AccountingEntry $entry) => [
                $entry->voucher->date->format('Y-m-d'),
                $entry->voucher->number,
                $entry->chartAccount->full_name,
                $entry->thirdParty?->name,
                $entry->description,
                $entry->debit,
                $entry->credit,
            ])->all());
        }

        return view('accounting-reports.ledger', [
            'entries' => $entries->paginate(50)->withQueryString(),
            'chartAccounts' => ChartAccount::query()->whereBelongsTo($company)->orderBy('code')->get(),
            'thirdParties' => ThirdParty::query()->whereBelongsTo($company)->orderBy('name')->get(),
            'filters' => $request->only(['starts_on', 'ends_on', 'chart_account_id', 'third_party_id']),
        ]);
    }

    public function trialBalance(Request $request): View|StreamedResponse
    {
        $company = $this->currentCompany->get();
        $query = AccountingEntry::query()
            ->select('chart_accounts.code', 'chart_accounts.name', DB::raw('sum(accounting_entries.debit) as debit_total'), DB::raw('sum(accounting_entries.credit) as credit_total'))
            ->join('chart_accounts', 'chart_accounts.id', '=', 'accounting_entries.chart_account_id')
            ->join('vouchers', 'vouchers.id', '=', 'accounting_entries.voucher_id')
            ->where('vouchers.company_id', $company->id)
            ->when($request->filled('starts_on'), fn ($query) => $query->whereDate('vouchers.date', '>=', $request->date('starts_on')))
            ->when($request->filled('ends_on'), fn ($query) => $query->whereDate('vouchers.date', '<=', $request->date('ends_on')))
            ->groupBy('chart_accounts.id', 'chart_accounts.code', 'chart_accounts.name')
            ->orderBy('chart_accounts.code');

        $rows = $query->get()->map(fn ($row) => [
            'code' => $row->code,
            'name' => $row->name,
            'debit_total' => (float) $row->debit_total,
            'credit_total' => (float) $row->credit_total,
            'balance' => round((float) $row->debit_total - (float) $row->credit_total, 2),
        ]);

        if ($request->boolean('export')) {
            return $this->downloadCsv('balance-comprobacion.csv', ['Cuenta', 'Nombre', 'Débito', 'Crédito', 'Saldo'], $rows->map(fn ($row) => array_values($row))->all());
        }

        return view('accounting-reports.trial-balance', [
            'rows' => $rows,
            'filters' => $request->only(['starts_on', 'ends_on']),
        ]);
    }

    public function thirdPartyMovements(Request $request): View|StreamedResponse
    {
        $entries = $this->ledgerQuery($request)->whereNotNull('third_party_id');

        if ($request->boolean('export')) {
            return $this->downloadCsv('movimientos-por-tercero.csv', ['Fecha', 'Tercero', 'Comprobante', 'Cuenta', 'Descripción', 'Débito', 'Crédito'], $entries->get()->map(fn (AccountingEntry $entry) => [
                $entry->voucher->date->format('Y-m-d'),
                $entry->thirdParty?->name,
                $entry->voucher->number,
                $entry->chartAccount->full_name,
                $entry->description,
                $entry->debit,
                $entry->credit,
            ])->all());
        }

        return view('accounting-reports.third-party-movements', [
            'entries' => $entries->paginate(50)->withQueryString(),
            'thirdParties' => ThirdParty::query()->whereBelongsTo($this->currentCompany->get())->orderBy('name')->get(),
            'filters' => $request->only(['starts_on', 'ends_on', 'third_party_id']),
        ]);
    }

    public function journal(Request $request): StreamedResponse
    {
        $company = $this->currentCompany->get();

        $entries = AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))
            ->join('vouchers', 'vouchers.id', '=', 'accounting_entries.voucher_id')
            ->when($request->filled('starts_on'), fn ($query) => $query->whereDate('vouchers.date', '>=', $request->date('starts_on')))
            ->when($request->filled('ends_on'), fn ($query) => $query->whereDate('vouchers.date', '<=', $request->date('ends_on')))
            ->when($request->filled('type'), fn ($query) => $query->where('vouchers.type', $request->string('type')))
            ->orderBy('vouchers.date')
            ->orderBy('vouchers.number')
            ->select('accounting_entries.*')
            ->get();

        return $this->downloadCsv('libro-diario.csv', ['Fecha', 'Comprobante', 'Tipo', 'Cuenta', 'Tercero', 'Descripción', 'Débito', 'Crédito'], $entries->map(fn (AccountingEntry $entry) => [
            $entry->voucher->date->format('Y-m-d'),
            $entry->voucher->number,
            $entry->voucher->type->getLabel(),
            $entry->chartAccount->full_name,
            $entry->thirdParty?->name,
            $entry->description,
            $entry->debit,
            $entry->credit,
        ])->all());
    }

    public function financialStatements(Request $request): StreamedResponse
    {
        $company = $this->currentCompany->get();
        $statement = app(FinancialStatement::class);

        $balanceSheet = $statement->balanceSheet($company, $request->input('ends_on'));
        $incomeStatement = $statement->incomeStatement($company, $request->input('starts_on'), $request->input('ends_on'));

        $rows = [];
        $rows[] = ['BALANCE GENERAL', '', ''];
        foreach ($balanceSheet['classes'] as $class) {
            foreach ($class['accounts'] as $account) {
                $rows[] = [$account['code'], $account['name'], number_format($account['balance'], 2, '.', '')];
            }
            $rows[] = ["Total {$class['label']}", '', number_format($class['total'], 2, '.', '')];
        }
        $rows[] = ['Resultado del ejercicio', '', number_format($balanceSheet['net_income'], 2, '.', '')];
        $rows[] = ['', '', ''];
        $rows[] = ['ESTADO DE RESULTADOS', '', ''];
        foreach ($incomeStatement['classes'] as $class) {
            foreach ($class['accounts'] as $account) {
                $rows[] = [$account['code'], $account['name'], number_format($account['balance'], 2, '.', '')];
            }
            $rows[] = ["Total {$class['label']}", '', number_format($class['total'], 2, '.', '')];
        }
        $rows[] = ['Resultado del ejercicio', '', number_format($incomeStatement['net_income'], 2, '.', '')];

        return $this->downloadCsv('estados-financieros.csv', ['Cuenta', 'Nombre', 'Saldo'], $rows);
    }

    public function generalLedger(Request $request): StreamedResponse
    {
        $rows = app(FinancialStatement::class)->generalLedger(
            $this->currentCompany->get(),
            $request->input('starts_on'),
            $request->input('ends_on'),
        );

        return $this->downloadCsv('libro-mayor.csv', ['Cuenta', 'Nombre', 'Saldo Inicial', 'Débito', 'Crédito', 'Saldo Final'], $rows->map(fn (array $row) => [
            $row['code'],
            $row['name'],
            $row['opening_balance'],
            $row['debit'],
            $row['credit'],
            $row['closing_balance'],
        ])->all());
    }

    public function bankReconciliation(Request $request): StreamedResponse
    {
        $company = $this->currentCompany->get();

        $cashAccount = CashAccount::query()
            ->whereBelongsTo($company)
            ->when($request->filled('cash_account_id'), fn ($query) => $query->where('id', $request->integer('cash_account_id')))
            ->orderBy('name')
            ->firstOrFail();

        $rows = app(BankReconciliation::class)->pendingItems($company, $cashAccount, $request->input('cutoff'));

        return $this->downloadCsv('conciliacion-bancaria.csv', ['Fecha', 'Comprobante', 'Tercero', 'Referencia', 'Monto', 'Conciliado'], $rows->map(fn (array $row) => [
            $row['date']->format('Y-m-d'),
            $row['voucher_number'],
            $row['third_party'],
            $row['reference'],
            $row['signed_amount'],
            $row['reconciled'] ? 'Sí' : 'No',
        ])->all());
    }

    public function accountsReceivable(): StreamedResponse
    {
        $rows = app(AccountsReceivable::class)->openItems($this->currentCompany->get());

        return $this->downloadCsv('cartera-clientes.csv', ['Tercero', 'Comprobante', 'Soporte', 'Fecha', 'Valor', 'Pagado', 'Saldo', 'Días', 'Edad'], $rows->map(fn (array $row) => [
            $row['third_party'],
            $row['voucher_number'],
            $row['support_number'],
            $row['accrual_date']->format('Y-m-d'),
            $row['amount'],
            $row['paid'],
            $row['pending'],
            $row['days_overdue'],
            $row['bucket'],
        ])->all());
    }

    private function ledgerQuery(Request $request)
    {
        $company = $this->currentCompany->get();

        return AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereHas('voucher', fn ($query) => $query->whereBelongsTo($company))
            ->when($request->filled('starts_on'), fn ($query) => $query->whereHas('voucher', fn ($query) => $query->whereDate('date', '>=', $request->date('starts_on'))))
            ->when($request->filled('ends_on'), fn ($query) => $query->whereHas('voucher', fn ($query) => $query->whereDate('date', '<=', $request->date('ends_on'))))
            ->when($request->filled('chart_account_id'), fn ($query) => $query->where('chart_account_id', $request->integer('chart_account_id')))
            ->when($request->filled('third_party_id'), fn ($query) => $query->where('third_party_id', $request->integer('third_party_id')))
            ->latest();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function downloadCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

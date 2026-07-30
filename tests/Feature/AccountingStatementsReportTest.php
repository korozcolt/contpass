<?php

use App\Enums\AccountNature;
use App\Enums\VoucherStatus;
use App\Filament\Pages\BalanceSheetReport;
use App\Filament\Pages\IncomeStatementReport;
use App\Filament\Pages\JournalReport;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\FinancialStatement;
use App\Services\Accounting\PostExpenseVoucher;
use App\Services\Accounting\PostIncomeVoucher;
use Livewire\Livewire;

function statementFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000001']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373913', 'verification_digit' => 4]);

    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);
    $payable = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '220505', 'name' => 'Proveedores']);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos operacionales']);
    $expense = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '513525', 'name' => 'Servicios', 'nature' => AccountNature::Debit]);

    return compact('company', 'thirdParty', 'receivable', 'payable', 'revenue', 'expense');
}

it('calculates net income as revenue minus expenses', function () {
    $data = statementFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-100',
        'accrual_date' => '2026-07-01',
        'amount' => 500000,
    ]);

    app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $data['expense']->id,
        'payable_account_id' => $data['payable']->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-100',
        'accrual_date' => '2026-07-02',
        'amount' => 200000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    $incomeStatement = app(FinancialStatement::class)->incomeStatement($data['company'], '2026-07-01', '2026-07-31');

    expect($incomeStatement['totals']['ingresos'])->toBe(500000.0)
        ->and($incomeStatement['totals']['gastos_costos'])->toBe(200000.0)
        ->and($incomeStatement['net_income'])->toBe(300000.0);
});

it('classifies balances by puc class and balances the sheet', function () {
    $data = statementFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-101',
        'accrual_date' => '2026-07-01',
        'amount' => 500000,
    ]);

    $balanceSheet = app(FinancialStatement::class)->balanceSheet($data['company'], '2026-07-31');

    $assetClass = $balanceSheet['classes']->firstWhere('class', '1');

    expect($balanceSheet['totals']['activo'])->toBe(500000.0)
        ->and($assetClass['accounts']->first()['code'])->toBe('130505')
        ->and($balanceSheet['net_income'])->toBe(500000.0)
        ->and($balanceSheet['is_balanced'])->toBeTrue();
});

it('excludes void vouchers from financial statements', function () {
    $data = statementFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-102',
        'accrual_date' => '2026-07-01',
        'amount' => 500000,
    ]);
    $income->update(['status' => VoucherStatus::Void]);

    $incomeStatement = app(FinancialStatement::class)->incomeStatement($data['company'], '2026-07-01', '2026-07-31');

    expect($incomeStatement['totals']['ingresos'])->toBe(0.0)
        ->and($incomeStatement['net_income'])->toBe(0.0);
});

it('renders the journal report grouped by voucher', function () {
    $this->actingAs(User::factory()->create());
    $data = statementFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-103',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    Livewire::test(JournalReport::class)->assertSuccessful();
});

it('renders the balance sheet and income statement reports', function () {
    $this->actingAs(User::factory()->create());
    $data = statementFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-104',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    Livewire::test(BalanceSheetReport::class)->assertSuccessful();
    Livewire::test(IncomeStatementReport::class)->assertSuccessful();
});

it('blocks the new report csv exports for guests', function () {
    $this->get(route('accounting-reports.journal', ['export' => 1]))->assertForbidden();
    $this->get(route('accounting-reports.financial-statements', ['export' => 1]))->assertForbidden();
});

it('exports the journal and financial statements as csv', function () {
    $this->actingAs(User::factory()->create());
    $data = statementFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-105',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    $this->get(route('accounting-reports.journal'))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $this->get(route('accounting-reports.financial-statements'))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

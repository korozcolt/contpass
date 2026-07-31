<?php

use App\Enums\AccountNature;
use App\Enums\VoucherStatus;
use App\Filament\Pages\GeneralLedgerReport;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\FinancialStatement;
use App\Services\Accounting\PostExpenseVoucher;
use App\Services\Accounting\PostIncomeVoucher;
use Livewire\Livewire;

function generalLedgerFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000002']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373914', 'verification_digit' => 5]);

    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);
    $payable = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '220505', 'name' => 'Proveedores']);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos operacionales']);
    $expense = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '513525', 'name' => 'Servicios', 'nature' => AccountNature::Debit]);

    return compact('company', 'thirdParty', 'receivable', 'payable', 'revenue', 'expense');
}

it('carries the opening balance from before the period and adds the period movement', function () {
    $data = generalLedgerFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-200',
        'accrual_date' => '2026-06-15',
        'amount' => 500000,
    ]);

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-201',
        'accrual_date' => '2026-07-10',
        'amount' => 200000,
    ]);

    $ledger = app(FinancialStatement::class)->generalLedger($data['company'], '2026-07-01', '2026-07-31');

    $receivableRow = $ledger->firstWhere('code', '130505');
    $revenueRow = $ledger->firstWhere('code', '413595');

    expect($receivableRow['opening_balance'])->toBe(500000.0)
        ->and($receivableRow['debit'])->toBe(200000.0)
        ->and($receivableRow['closing_balance'])->toBe(700000.0)
        ->and($revenueRow['opening_balance'])->toBe(500000.0)
        ->and($revenueRow['credit'])->toBe(200000.0)
        ->and($revenueRow['closing_balance'])->toBe(700000.0);
});

it('has a zero opening balance when no start date filter is given', function () {
    $data = generalLedgerFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-206',
        'accrual_date' => '2026-07-01',
        'amount' => 300000,
    ]);

    $ledger = app(FinancialStatement::class)->generalLedger($data['company']);

    $receivableRow = $ledger->firstWhere('code', '130505');

    expect($receivableRow['opening_balance'])->toBe(0.0)
        ->and($receivableRow['debit'])->toBe(300000.0)
        ->and($receivableRow['closing_balance'])->toBe(300000.0);
});

it('excludes void vouchers from opening and period balances', function () {
    $data = generalLedgerFixture();

    $voided = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-202',
        'accrual_date' => '2026-06-15',
        'amount' => 500000,
    ]);
    $voided->update(['status' => VoucherStatus::Void]);

    app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $data['expense']->id,
        'payable_account_id' => $data['payable']->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-200',
        'accrual_date' => '2026-07-05',
        'amount' => 150000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    $ledger = app(FinancialStatement::class)->generalLedger($data['company'], '2026-07-01', '2026-07-31');

    $receivableRow = $ledger->firstWhere('code', '130505');
    $expenseRow = $ledger->firstWhere('code', '513525');

    expect($receivableRow)->toBeNull()
        ->and($expenseRow['opening_balance'])->toBe(0.0)
        ->and($expenseRow['debit'])->toBe(150000.0)
        ->and($expenseRow['closing_balance'])->toBe(150000.0);
});

it('renders the general ledger report', function () {
    $this->actingAs(User::factory()->create());
    $data = generalLedgerFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-203',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    Livewire::test(GeneralLedgerReport::class)->assertSuccessful();
});

it('blocks the general ledger csv export for guests', function () {
    $this->get(route('accounting-reports.general-ledger', ['export' => 1]))->assertForbidden();
});

it('exports the general ledger as csv', function () {
    $this->actingAs(User::factory()->create());
    $data = generalLedgerFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-204',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    $this->get(route('accounting-reports.general-ledger'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

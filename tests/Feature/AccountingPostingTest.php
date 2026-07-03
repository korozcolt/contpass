<?php

use App\Enums\AccountNature;
use App\Enums\CashAccountType;
use App\Enums\PaymentMethod;
use App\Models\AccountingPeriod;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WithholdingRule;
use App\Services\Accounting\ApplyWithholdingRules;
use App\Services\Accounting\CreateAdjustmentVoucher;
use App\Services\Accounting\PostExpenseVoucher;
use App\Services\Accounting\PostIncomeVoucher;
use App\Services\Accounting\RegisterPayment;
use Illuminate\Validation\ValidationException;

function accountingFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000000']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373913', 'verification_digit' => 4]);
    $bank = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '111005', 'name' => 'Bancos', 'nature' => AccountNature::Debit]);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);
    $payable = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '220505', 'name' => 'Proveedores']);
    $withholding = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '236540', 'name' => 'Retención']);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos']);
    $expense = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '513525', 'name' => 'Servicios', 'nature' => AccountNature::Debit]);
    $cashAccount = CashAccount::factory()->create(['company_id' => $company->id, 'chart_account_id' => $bank->id, 'type' => CashAccountType::Bank]);

    WithholdingRule::factory()->create([
        'company_id' => $company->id,
        'chart_account_id' => $withholding->id,
        'minimum_base' => 100000,
        'rate' => 4,
        'starts_on' => '2026-01-01',
    ]);

    return compact('company', 'thirdParty', 'bank', 'receivable', 'payable', 'withholding', 'revenue', 'expense', 'cashAccount');
}

it('applies active withholding rules by base and date', function () {
    ['company' => $company] = accountingFixture();

    $withholdings = app(ApplyWithholdingRules::class)->handle($company, 200000, '2026-07-01');

    expect($withholdings)->toHaveCount(1)
        ->and($withholdings->first()['amount'])->toBe(8000.0);
});

it('validates balanced voucher entries', function () {
    expect(Voucher::entriesAreBalanced([
        ['debit' => 100000, 'credit' => 0],
        ['debit' => 0, 'credit' => 100000],
    ]))->toBeTrue()
        ->and(Voucher::entriesAreBalanced([
            ['debit' => 100000, 'credit' => 0],
            ['debit' => 0, 'credit' => 90000],
        ]))->toBeFalse();
});

it('posts income and expense vouchers with balanced entries', function () {
    $data = accountingFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-001',
        'accrual_date' => '2026-07-01',
        'amount' => 500000,
    ]);

    $expense = app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $data['expense']->id,
        'payable_account_id' => $data['payable']->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-001',
        'accrual_date' => '2026-07-01',
        'amount' => 200000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    expect($income->isBalanced())->toBeTrue()
        ->and($expense->isBalanced())->toBeTrue()
        ->and($expense->expenseRecord->withholding_amount)->toEqual('8000.00');
});

it('blocks posting in closed accounting periods', function () {
    $data = accountingFixture();
    AccountingPeriod::factory()->closed()->create(['company_id' => $data['company']->id, 'starts_on' => '2026-07-01', 'ends_on' => '2026-07-31']);

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-002',
        'accrual_date' => '2026-07-15',
        'amount' => 100000,
    ]);
})->throws(ValidationException::class);

it('registers payments and marks cash as not bancarized', function () {
    $data = accountingFixture();
    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-003',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    $paymentVoucher = app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'source_voucher_id' => $income->id,
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => '2026-07-02',
        'amount' => 100000,
    ], $income);

    expect($paymentVoucher->isBalanced())->toBeTrue()
        ->and($paymentVoucher->payment->is_bancarized)->toBeFalse();
});

it('creates adjustment vouchers for approved vouchers', function () {
    $data = accountingFixture();
    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-ADJ',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    $adjustment = app(CreateAdjustmentVoucher::class)->handle($data['company'], $income, '2026-07-03', 'Ajuste ingreso REC-ADJ', [
        [
            'chart_account_id' => $data['revenue']->id,
            'third_party_id' => $data['thirdParty']->id,
            'description' => 'Reversión parcial',
            'debit' => 10000,
            'credit' => 0,
        ],
        [
            'chart_account_id' => $data['receivable']->id,
            'third_party_id' => $data['thirdParty']->id,
            'description' => 'Reversión parcial',
            'debit' => 0,
            'credit' => 10000,
        ],
    ]);

    expect($adjustment->isBalanced())->toBeTrue()
        ->and($adjustment->adjusts_voucher_id)->toBe($income->id)
        ->and($income->refresh()->status->value)->toBe('adjusted');
});

it('blocks accounting csv exports for guests', function () {
    $this->get(route('accounting-reports.ledger', ['export' => 1]))->assertForbidden();
    $this->get(route('accounting-reports.third-party-movements', ['export' => 1]))->assertForbidden();
    $this->get(route('accounting-reports.trial-balance', ['export' => 1]))->assertForbidden();
});

it('exports ledger and trial balance as csv', function () {
    $this->actingAs(User::factory()->create());
    $data = accountingFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-004',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);

    $this->get(route('accounting-reports.ledger', ['export' => 1]))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $this->get(route('accounting-reports.third-party-movements', ['export' => 1]))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $this->get(route('accounting-reports.trial-balance', ['export' => 1]))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

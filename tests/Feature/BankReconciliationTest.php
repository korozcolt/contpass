<?php

use App\Enums\AccountNature;
use App\Enums\CashAccountType;
use App\Enums\PaymentMethod;
use App\Filament\Pages\BankReconciliationReport;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Payment;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\BankReconciliation;
use App\Services\Accounting\PostExpenseVoucher;
use App\Services\Accounting\PostIncomeVoucher;
use App\Services\Accounting\RegisterPayment;
use Livewire\Livewire;

function bankReconciliationFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000003']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373915', 'verification_digit' => 6]);

    $bank = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '111005', 'name' => 'Bancos', 'nature' => AccountNature::Debit]);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);
    $payable = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '220505', 'name' => 'Proveedores']);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos']);
    $expense = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '513525', 'name' => 'Servicios', 'nature' => AccountNature::Debit]);
    $cashAccount = CashAccount::factory()->create(['company_id' => $company->id, 'chart_account_id' => $bank->id, 'type' => CashAccountType::Bank]);

    return compact('company', 'thirdParty', 'bank', 'receivable', 'payable', 'revenue', 'expense', 'cashAccount');
}

it('persists reconciled_at through the is_reconciled virtual attribute', function () {
    $data = bankReconciliationFixture();
    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-300',
        'accrual_date' => '2026-07-01',
        'amount' => 300000,
    ]);

    $voucher = app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => '2026-07-02',
        'amount' => 300000,
    ], $income);

    $payment = Payment::query()->where('voucher_id', $voucher->id)->firstOrFail();

    expect($payment->is_reconciled)->toBeFalse();

    $payment->update(['is_reconciled' => true]);

    expect($payment->fresh()->is_reconciled)->toBeTrue()
        ->and($payment->fresh()->reconciled_at)->not->toBeNull();

    $payment->update(['is_reconciled' => false]);

    expect($payment->fresh()->is_reconciled)->toBeFalse()
        ->and($payment->fresh()->reconciled_at)->toBeNull();
});

it('summarizes book, reconciled and pending balances for a cash account', function () {
    $data = bankReconciliationFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-301',
        'accrual_date' => '2026-07-01',
        'amount' => 400000,
    ]);
    $incomeVoucher = app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => '2026-07-02',
        'amount' => 400000,
    ], $income);
    Payment::query()->where('voucher_id', $incomeVoucher->id)->firstOrFail()->update(['is_reconciled' => true]);

    $expense = app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $data['expense']->id,
        'payable_account_id' => $data['payable']->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-300',
        'accrual_date' => '2026-07-03',
        'amount' => 150000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);
    app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['payable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => '2026-07-04',
        'amount' => 150000,
    ], $expense);

    $summary = app(BankReconciliation::class)->summary($data['company'], $data['cashAccount']);

    expect($summary['book_balance'])->toBe(250000.0)
        ->and($summary['reconciled_balance'])->toBe(400000.0)
        ->and($summary['pending_balance'])->toBe(-150000.0);
});

it('lists only unreconciled payments as pending', function () {
    $data = bankReconciliationFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-302',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);
    $voucher = app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => '2026-07-02',
        'amount' => 100000,
    ], $income);

    $pending = app(BankReconciliation::class)->pendingItems($data['company'], $data['cashAccount']);
    expect($pending)->toHaveCount(1);

    Payment::query()->where('voucher_id', $voucher->id)->firstOrFail()->update(['is_reconciled' => true]);

    $pending = app(BankReconciliation::class)->pendingItems($data['company'], $data['cashAccount']);
    expect($pending)->toHaveCount(0);
});

it('toggles is_reconciled from the payments table', function () {
    $this->actingAs(User::factory()->create());
    $data = bankReconciliationFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-303',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
    ]);
    $voucher = app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => '2026-07-02',
        'amount' => 100000,
    ], $income);
    $payment = Payment::query()->where('voucher_id', $voucher->id)->firstOrFail();

    Livewire::test(ListPayments::class)
        ->call('updateTableColumnState', 'is_reconciled', (string) $payment->getKey(), true);

    expect($payment->fresh()->is_reconciled)->toBeTrue();
});

it('renders the bank reconciliation report', function () {
    $this->actingAs(User::factory()->create());
    $data = bankReconciliationFixture();

    Livewire::test(BankReconciliationReport::class)->assertSuccessful();
});

it('blocks the bank reconciliation csv export for guests', function () {
    $data = bankReconciliationFixture();

    $this->get(route('accounting-reports.bank-reconciliation', ['cash_account_id' => $data['cashAccount']->id]))->assertForbidden();
});

it('exports the bank reconciliation report as csv', function () {
    $this->actingAs(User::factory()->create());
    $data = bankReconciliationFixture();

    $this->get(route('accounting-reports.bank-reconciliation', ['cash_account_id' => $data['cashAccount']->id]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

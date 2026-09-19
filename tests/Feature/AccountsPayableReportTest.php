<?php

use App\Enums\AccountNature;
use App\Enums\BudgetObligationStatus;
use App\Enums\CashAccountType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOrderStatus;
use App\Filament\Pages\AccountsPayableReport;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\ThirdParty;
use App\Models\User;
use App\Models\WithholdingRule;
use App\Services\Accounting\AccountsPayable;
use App\Services\Accounting\PostExpenseVoucher;
use App\Services\Accounting\RegisterPayment;
use Livewire\Livewire;

function payableFixture(array $obligationOverrides = []): array
{
    $company = Company::factory()->create(['tax_id' => '900000003', 'has_budgetary_control' => true]);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373914', 'verification_digit' => 5]);

    $appropriation = BudgetAppropriation::factory()->for($company)->create();
    $certificate = BudgetAvailabilityCertificate::factory()->create([
        'company_id' => $company->id,
        'budget_appropriation_id' => $appropriation->id,
    ]);
    $registration = BudgetRegistration::factory()->create([
        'company_id' => $company->id,
        'budget_availability_certificate_id' => $certificate->id,
        'third_party_id' => $thirdParty->id,
    ]);
    $obligation = BudgetObligation::factory()->approved()->create(array_merge([
        'company_id' => $company->id,
        'budget_registration_id' => $registration->id,
        'amount' => 500000,
        'accrual_date' => now()->toDateString(),
    ], $obligationOverrides));
    $paymentOrder = PaymentOrder::factory()->create([
        'company_id' => $company->id,
        'budget_obligation_id' => $obligation->id,
        'status' => PaymentOrderStatus::Approved,
        'amount' => $obligation->amount,
    ]);

    return compact('company', 'thirdParty', 'obligation', 'paymentOrder');
}

function privateExpenseFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000005']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373916', 'verification_digit' => 6]);

    $bank = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '111005', 'name' => 'Bancos', 'nature' => AccountNature::Debit]);
    $expenseAccount = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '513525', 'name' => 'Servicios', 'nature' => AccountNature::Debit]);
    $payableAccount = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '220505', 'name' => 'Proveedores']);
    $withholdingAccount = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '236540', 'name' => 'Retención']);
    $cashAccount = CashAccount::factory()->create(['company_id' => $company->id, 'chart_account_id' => $bank->id, 'type' => CashAccountType::Bank]);

    WithholdingRule::factory()->create([
        'company_id' => $company->id,
        'chart_account_id' => $withholdingAccount->id,
        'minimum_base' => 100000,
        'rate' => 4,
        'starts_on' => '2026-01-01',
    ]);

    return compact('company', 'thirdParty', 'expenseAccount', 'payableAccount', 'cashAccount');
}

it('lists an unpaid obligation as fully pending', function () {
    $data = payableFixture();

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['pending'])->toBe(500000.0)
        ->and($rows->first()['bucket'])->toBe('Corriente');
});

it('excludes a fully paid obligation', function () {
    $data = payableFixture();

    Payment::factory()->create([
        'payment_order_id' => $data['paymentOrder']->id,
        'paid_on' => now()->toDateString(),
        'amount' => 500000,
    ]);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(0);
});

it('leaves the correct balance after a partial payment', function () {
    $data = payableFixture();

    Payment::factory()->create([
        'payment_order_id' => $data['paymentOrder']->id,
        'paid_on' => now()->toDateString(),
        'amount' => 200000,
    ]);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['paid'])->toBe(200000.0)
        ->and($rows->first()['pending'])->toBe(300000.0);
});

it('buckets overdue payables by their age', function () {
    $data = payableFixture(['accrual_date' => now()->subDays(100)->toDateString()]);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows->first()['bucket'])->toBe('+90 días')
        ->and($rows->first()['days_overdue'])->toBeGreaterThanOrEqual(100);
});

it('excludes cancelled obligations', function () {
    $data = payableFixture();
    $data['obligation']->update(['status' => BudgetObligationStatus::Cancelled]);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(0);
});

it('includes a private-market expense record at its net-of-withholding pending amount', function () {
    $data = privateExpenseFixture();

    $voucher = app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $data['expenseAccount']->id,
        'payable_account_id' => $data['payableAccount']->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-500',
        'accrual_date' => now()->toDateString(),
        'amount' => 200000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['budget_obligation_id'])->toBeNull()
        ->and($rows->first()['number'])->toBe($voucher->number)
        ->and($rows->first()['amount'])->toBe(192000.0)
        ->and($rows->first()['pending'])->toBe(192000.0);
});

it('excludes a fully paid private-market expense record', function () {
    $data = privateExpenseFixture();

    $voucher = app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $data['expenseAccount']->id,
        'payable_account_id' => $data['payableAccount']->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-501',
        'accrual_date' => now()->toDateString(),
        'amount' => 200000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['payableAccount']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => now()->toDateString(),
        'amount' => 192000,
    ], $voucher);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(0);
});

it('combines budget obligation and private-market expense rows for the same company', function () {
    $data = payableFixture();

    $expenseAccount = ChartAccount::factory()->create(['company_id' => $data['company']->id, 'code' => '513530', 'name' => 'Honorarios', 'nature' => AccountNature::Debit]);
    $payableAccount = ChartAccount::factory()->credit()->create(['company_id' => $data['company']->id, 'code' => '220510', 'name' => 'Proveedores mercado privado']);

    app(PostExpenseVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'expense_account_id' => $expenseAccount->id,
        'payable_account_id' => $payableAccount->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-600',
        'accrual_date' => now()->toDateString(),
        'amount' => 100000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    $rows = app(AccountsPayable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('budget_obligation_id')->filter()->count())->toBe(1)
        ->and($rows->pluck('budget_obligation_id')->filter(fn ($id): bool => $id === null)->count())->toBe(1);
});

it('renders the accounts payable report', function () {
    $this->actingAs(User::factory()->create());
    payableFixture();

    Livewire::test(AccountsPayableReport::class)->assertSuccessful();
});

it('blocks the accounts payable csv export for guests', function () {
    $this->get(route('accounting-reports.accounts-payable'))->assertForbidden();
});

it('exports the accounts payable report as csv', function () {
    $this->actingAs(User::factory()->create());
    payableFixture();

    $this->get(route('accounting-reports.accounts-payable'))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

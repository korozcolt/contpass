<?php

use App\Enums\AccountNature;
use App\Enums\CashAccountType;
use App\Enums\PaymentMethod;
use App\Filament\Pages\AccountsReceivableReport;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\AccountsReceivable;
use App\Services\Accounting\PostIncomeVoucher;
use App\Services\Accounting\RegisterPayment;
use Livewire\Livewire;

function receivableFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000002']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373913', 'verification_digit' => 4]);

    $bank = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '111005', 'name' => 'Bancos', 'nature' => AccountNature::Debit]);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos']);
    $cashAccount = CashAccount::factory()->create(['company_id' => $company->id, 'chart_account_id' => $bank->id, 'type' => CashAccountType::Bank]);

    return compact('company', 'thirdParty', 'bank', 'receivable', 'revenue', 'cashAccount');
}

it('lists an unpaid income record as fully pending', function () {
    $data = receivableFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-200',
        'accrual_date' => now()->toDateString(),
        'amount' => 500000,
    ]);

    $rows = app(AccountsReceivable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['pending'])->toBe(500000.0)
        ->and($rows->first()['bucket'])->toBe('Corriente');
});

it('excludes a fully paid income record', function () {
    $data = receivableFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-201',
        'accrual_date' => now()->toDateString(),
        'amount' => 500000,
    ]);

    app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => now()->toDateString(),
        'amount' => 500000,
    ], $income);

    $rows = app(AccountsReceivable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(0);
});

it('leaves the correct balance after a partial payment', function () {
    $data = receivableFixture();

    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-202',
        'accrual_date' => now()->toDateString(),
        'amount' => 500000,
    ]);

    app(RegisterPayment::class)->handle($data['company'], $data['cashAccount'], [
        'cash_account_id' => $data['cashAccount']->id,
        'counterparty_account_id' => $data['receivable']->id,
        'method' => PaymentMethod::Cash->value,
        'paid_on' => now()->toDateString(),
        'amount' => 200000,
    ], $income);

    $rows = app(AccountsReceivable::class)->openItems($data['company']);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['paid'])->toBe(200000.0)
        ->and($rows->first()['pending'])->toBe(300000.0);
});

it('buckets overdue receivables by their age', function () {
    $data = receivableFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-203',
        'accrual_date' => now()->subDays(100)->toDateString(),
        'amount' => 100000,
    ]);

    $rows = app(AccountsReceivable::class)->openItems($data['company']);

    expect($rows->first()['bucket'])->toBe('+90 días')
        ->and($rows->first()['days_overdue'])->toBeGreaterThanOrEqual(100);
});

it('renders the accounts receivable report', function () {
    $this->actingAs(User::factory()->create());
    $data = receivableFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-204',
        'accrual_date' => now()->toDateString(),
        'amount' => 100000,
    ]);

    Livewire::test(AccountsReceivableReport::class)->assertSuccessful();
});

it('blocks the accounts receivable csv export for guests', function () {
    $this->get(route('accounting-reports.accounts-receivable'))->assertForbidden();
});

it('exports the accounts receivable report as csv', function () {
    $this->actingAs(User::factory()->create());
    $data = receivableFixture();

    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'third_party_id' => $data['thirdParty']->id,
        'revenue_account_id' => $data['revenue']->id,
        'receivable_account_id' => $data['receivable']->id,
        'support_number' => 'REC-205',
        'accrual_date' => now()->toDateString(),
        'amount' => 100000,
    ]);

    $this->get(route('accounting-reports.accounts-receivable'))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

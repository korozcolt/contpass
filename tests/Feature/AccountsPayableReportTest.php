<?php

use App\Enums\BudgetObligationStatus;
use App\Enums\PaymentOrderStatus;
use App\Filament\Pages\AccountsPayableReport;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\AccountsPayable;
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

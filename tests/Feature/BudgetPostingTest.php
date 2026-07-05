<?php

use App\Enums\AccountNature;
use App\Enums\BudgetCertificateStatus;
use App\Enums\BudgetObligationStatus;
use App\Enums\BudgetRegistrationStatus;
use App\Enums\CashAccountType;
use App\Enums\PaymentOrderStatus;
use App\Filament\Resources\ExpenseRecords\ExpenseRecordResource;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetChartMapping;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\ApplyBudgetRegistration;
use App\Services\Budget\ApproveBudgetObligation;
use App\Services\Budget\CreateBudgetObligation;
use App\Services\Budget\ExecutePaymentOrder;
use App\Services\Budget\IssueBudgetCertificate;
use App\Services\Budget\IssuePaymentOrder;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\mock;

function budgetFixture(): array
{
    $company = Company::factory()->create([
        'has_budgetary_control' => true,
        'tax_id' => '800000000',
    ]);

    $expenseAccount = ChartAccount::factory()->create([
        'company_id' => $company->id,
        'code' => '513525',
        'name' => 'Servicios',
        'nature' => AccountNature::Debit,
    ]);

    $payableAccount = ChartAccount::factory()->credit()->create([
        'company_id' => $company->id,
        'code' => '233525',
        'name' => 'Costos y gastos por pagar',
    ]);

    $bank = ChartAccount::factory()->create([
        'company_id' => $company->id,
        'code' => '111005',
        'name' => 'Bancos',
        'nature' => AccountNature::Debit,
    ]);

    $cashAccount = CashAccount::factory()->create([
        'company_id' => $company->id,
        'chart_account_id' => $bank->id,
        'type' => CashAccountType::Bank,
    ]);

    $appropriation = BudgetAppropriation::factory()->create([
        'company_id' => $company->id,
        'initial_amount' => 100_000_000,
    ]);

    $thirdParty = ThirdParty::factory()->create([
        'company_id' => $company->id,
        'tax_id' => '900373913',
        'verification_digit' => 4,
    ]);

    BudgetChartMapping::factory()->create([
        'company_id' => $company->id,
        'budget_appropriation_id' => $appropriation->id,
        'expense_chart_account_id' => $expenseAccount->id,
        'payable_chart_account_id' => $payableAccount->id,
    ]);

    return compact('company', 'appropriation', 'thirdParty', 'expenseAccount', 'payableAccount', 'bank', 'cashAccount');
}

it('blocks cdp when amount exceeds available appropriation balance', function () {
    $data = budgetFixture();

    expect(fn (): BudgetAvailabilityCertificate => app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        200_000_000,
        'Obra sobredimensionada',
        now()->toDateString(),
    ))->toThrow(ValidationException::class);
});

it('issues cdp and reduces available appropriation balance', function () {
    $data = budgetFixture();
    $availableBefore = $data['appropriation']->available_amount;

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        30_000_000,
        'Servicios profesionales',
        now()->toDateString(),
    );

    expect($cdp->status)->toBe(BudgetCertificateStatus::Active)
        ->and((float) $cdp->amount)->toBe(30_000_000.0)
        ->and($cdp->budgetAppropriation->is($data['appropriation']))->toBeTrue()
        ->and($data['appropriation']->refresh()->available_amount)->toBe($availableBefore - 30_000_000);
});

it('allows multiple rps on same cdp', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        50_000_000,
        'Adquisición de equipos',
        now()->toDateString(),
    );

    $rp1 = app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        20_000_000,
        'Contrato parcial A',
        now()->toDateString(),
    );

    $rp2 = app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        30_000_000,
        'Contrato parcial B',
        now()->toDateString(),
    );

    expect($rp1->status)->toBe(BudgetRegistrationStatus::Active)
        ->and($rp2->status)->toBe(BudgetRegistrationStatus::Active)
        ->and($cdp->refresh()->available_for_registration)->toBe(0.0);
});

it('marks cdp as fully_committed when balance reaches zero', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        40_000_000,
        'Compra de mobiliario',
        now()->toDateString(),
    );

    app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        40_000_000,
        'Contrato único',
        now()->toDateString(),
    );

    expect($cdp->refresh()->status)->toBe(BudgetCertificateStatus::FullyCommitted);
});

it('blocks rp on non-active cdp', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        10_000_000,
        'Objeto',
        now()->toDateString(),
    );

    $cdp->forceFill(['status' => BudgetCertificateStatus::Cancelled])->saveQuietly();

    expect(fn (): BudgetRegistration => app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp->refresh(),
        $data['thirdParty'],
        5_000_000,
        'Contrato inválido',
        now()->toDateString(),
    ))->toThrow(ValidationException::class);
});

it('creates obligation in draft without posting to puc', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        30_000_000,
        'Servicios',
        now()->toDateString(),
    );

    $rp = app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        30_000_000,
        'Contrato',
        now()->toDateString(),
    );

    $voucherCountBefore = Voucher::query()->count();

    $obligation = app(CreateBudgetObligation::class)->handle(
        $data['company'],
        $rp,
        10_000_000,
        'Factura',
        'FV-101',
        now()->toDateString(),
        'Servicios de consultoría',
    );

    expect($obligation->status)->toBe(BudgetObligationStatus::Draft)
        ->and($obligation->voucher_id)->toBeNull()
        ->and(Voucher::query()->count())->toBe($voucherCountBefore);
});

it('approves obligation and auto-posts balanced voucher', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        30_000_000,
        'Servicios TI',
        now()->toDateString(),
    );

    $rp = app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        30_000_000,
        'Contrato TI',
        now()->toDateString(),
    );

    $obligation = app(CreateBudgetObligation::class)->handle(
        $data['company'],
        $rp,
        5_000_000,
        'Factura',
        'FV-202',
        now()->toDateString(),
        'Desarrollo de software',
    );

    $approved = app(ApproveBudgetObligation::class)->handle(
        $data['company'],
        $obligation,
    );

    expect($approved->status)->toBe(BudgetObligationStatus::Approved)
        ->and($approved->voucher_id)->not->toBeNull()
        ->and($approved->approved_at)->not->toBeNull()
        ->and($approved->voucher->entries()->count())->toBeGreaterThanOrEqual(2)
        ->and((float) $approved->voucher->entries()->sum('debit'))->toEqualWithDelta((float) $approved->voucher->entries()->sum('credit'), 0.01);
});

it('blocks obligation approval without chart mapping', function () {
    $company = Company::factory()->create(['has_budgetary_control' => true, 'tax_id' => '800000001']);

    $appropriationWithoutMapping = BudgetAppropriation::factory()->create([
        'company_id' => $company->id,
        'initial_amount' => 50_000_000,
    ]);

    expect($appropriationWithoutMapping->chartMapping)->toBeNull();

    $expenseAccount = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '513595', 'name' => 'Honorarios', 'nature' => AccountNature::Debit]);
    $payableAccount = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '233595', 'name' => 'Honorarios por pagar']);

    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id]);

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $company,
        $appropriationWithoutMapping,
        20_000_000,
        'Honorarios',
        now()->toDateString(),
    );

    $rp = app(ApplyBudgetRegistration::class)->handle(
        $company,
        $cdp,
        $thirdParty,
        20_000_000,
        'Contrato honorarios',
        now()->toDateString(),
    );

    $obligation = app(CreateBudgetObligation::class)->handle(
        $company,
        $rp,
        5_000_000,
        'Factura',
        'FV-303',
        now()->toDateString(),
        'Honorarios profesionales',
    );

    expect(fn (): BudgetObligation => app(ApproveBudgetObligation::class)->handle(
        $company,
        $obligation,
    ))->toThrow(ValidationException::class);
});

it('creates payment order from approved obligation', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        20_000_000,
        'Mantenimiento',
        now()->toDateString(),
    );

    $rp = app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        20_000_000,
        'Contrato mantenimiento',
        now()->toDateString(),
    );

    $obligation = app(CreateBudgetObligation::class)->handle(
        $data['company'],
        $rp,
        10_000_000,
        'Factura',
        'FV-404',
        now()->toDateString(),
        'Mantenimiento preventivo',
    );

    $approved = app(ApproveBudgetObligation::class)->handle($data['company'], $obligation);

    $op = app(IssuePaymentOrder::class)->handle(
        $data['company'],
        $approved,
        $data['cashAccount'],
        10_000_000,
        'bank_transfer',
        'Pago mantenimiento',
        now()->toDateString(),
    );

    expect($op->status)->toBe(PaymentOrderStatus::Pending)
        ->and((float) $op->amount)->toBe(10_000_000.0)
        ->and($op->budgetObligation->is($approved))->toBeTrue()
        ->and($op->cashAccount->is($data['cashAccount']))->toBeTrue();
});

it('executes payment order and auto-registers payment', function () {
    $data = budgetFixture();

    $cdp = app(IssueBudgetCertificate::class)->handle(
        $data['company'],
        $data['appropriation'],
        20_000_000,
        'Compra suministros',
        now()->toDateString(),
    );

    $rp = app(ApplyBudgetRegistration::class)->handle(
        $data['company'],
        $cdp,
        $data['thirdParty'],
        20_000_000,
        'Contrato suministros',
        now()->toDateString(),
    );

    $obligation = app(CreateBudgetObligation::class)->handle(
        $data['company'],
        $rp,
        8_000_000,
        'Factura',
        'FV-505',
        now()->toDateString(),
        'Suministros de oficina',
    );

    $approved = app(ApproveBudgetObligation::class)->handle($data['company'], $obligation);

    $op = app(IssuePaymentOrder::class)->handle(
        $data['company'],
        $approved,
        $data['cashAccount'],
        8_000_000,
        'bank_transfer',
        'Pago suministros',
        now()->toDateString(),
    );

    $op->forceFill(['status' => PaymentOrderStatus::Approved])->saveQuietly();

    $executed = app(ExecutePaymentOrder::class)->handle(
        $data['company'],
        $op->fresh(),
        now()->toDateString(),
        'TRF-999',
    );

    expect($executed->status)->toBe(PaymentOrderStatus::Paid)
        ->and($executed->paid_on)->not->toBeNull()
        ->and($executed->voucher_id)->not->toBeNull()
        ->and($executed->voucher->entries()->count())->toBeGreaterThanOrEqual(2)
        ->and((float) $executed->voucher->entries()->sum('debit'))->toEqualWithDelta((float) $executed->voucher->entries()->sum('credit'), 0.01);
});

it('direct expense form is blocked when budgetary control is on', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['has_budgetary_control' => true, 'tax_id' => '800000003']);

    mock(CurrentCompany::class)->shouldReceive('get')->andReturn($company);

    $reflector = new ReflectionClass(ExpenseRecordResource::class);

    expect($reflector->getMethod('canCreate')->invoke(null))->toBeFalse();
});

<?php

use App\Enums\VoucherType;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\PaymentOrder;
use App\Models\ThirdParty;
use App\Models\Voucher;
use App\Models\WarehouseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('automatically generates voucher number by type and company', function () {
    $company = Company::factory()->create();
    $year = now()->format('Y');

    $voucher1 = Voucher::factory()->for($company)->create([
        'type' => VoucherType::Income,
        'number' => null,
    ]);

    $voucher2 = Voucher::factory()->for($company)->create([
        'type' => VoucherType::Income,
        'number' => null,
    ]);

    $voucherExpense = Voucher::factory()->for($company)->create([
        'type' => VoucherType::Expense,
        'number' => null,
    ]);

    expect($voucher1->number)->toBe("ING-{$year}-000001")
        ->and($voucher2->number)->toBe("ING-{$year}-000002")
        ->and($voucherExpense->number)->toBe("EGR-{$year}-000001");
});

it('increments voucher numbering correlatively and uniquely', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $year = now()->format('Y');

    $voucherA = Voucher::factory()->for($companyA)->create([
        'type' => VoucherType::Income,
        'number' => null,
    ]);

    $voucherB = Voucher::factory()->for($companyB)->create([
        'type' => VoucherType::Income,
        'number' => null,
    ]);

    expect($voucherA->number)->toBe("ING-{$year}-000001")
        ->and($voucherB->number)->toBe("ING-{$year}-000002");
});

it('automatically generates budget certificates (CDP) number', function () {
    $company = Company::factory()->create();
    $rubro = BudgetAppropriation::factory()->for($company)->create(['fiscal_year' => 2026]);

    $cdp = BudgetAvailabilityCertificate::factory()->for($company)->create([
        'budget_appropriation_id' => $rubro->id,
        'fiscal_year' => 2026,
        'number' => null,
    ]);

    expect($cdp->number)->toBe('CDP-2026-000001');
});

it('automatically generates budget registrations (RP) number', function () {
    $company = Company::factory()->create();
    $rubro = BudgetAppropriation::factory()->for($company)->create(['fiscal_year' => 2026]);
    $cdp = BudgetAvailabilityCertificate::factory()->for($company)->create([
        'budget_appropriation_id' => $rubro->id,
        'fiscal_year' => 2026,
    ]);
    $thirdParty = ThirdParty::factory()->for($company)->create();

    $rp = BudgetRegistration::factory()->for($company)->create([
        'budget_availability_certificate_id' => $cdp->id,
        'third_party_id' => $thirdParty->id,
        'fiscal_year' => 2026,
        'number' => null,
    ]);

    expect($rp->number)->toBe('RP-2026-000001');
});

it('automatically generates budget obligations number', function () {
    $company = Company::factory()->create();
    $rubro = BudgetAppropriation::factory()->for($company)->create(['fiscal_year' => 2026]);
    $cdp = BudgetAvailabilityCertificate::factory()->for($company)->create([
        'budget_appropriation_id' => $rubro->id,
        'fiscal_year' => 2026,
    ]);
    $thirdParty = ThirdParty::factory()->for($company)->create();
    $rp = BudgetRegistration::factory()->for($company)->create([
        'budget_availability_certificate_id' => $cdp->id,
        'third_party_id' => $thirdParty->id,
        'fiscal_year' => 2026,
    ]);

    $obligation = BudgetObligation::factory()->for($company)->create([
        'budget_registration_id' => $rp->id,
        'fiscal_year' => 2026,
        'number' => null,
    ]);

    expect($obligation->number)->toBe('OBL-2026-000001');
});

it('automatically generates payment orders (OP) number', function () {
    $company = Company::factory()->create();
    $chartAccount = ChartAccount::factory()->for($company)->create(['code' => '111005']);
    $cashAccount = CashAccount::factory()->for($company)->create(['chart_account_id' => $chartAccount->id]);
    $rubro = BudgetAppropriation::factory()->for($company)->create(['fiscal_year' => 2026]);
    $cdp = BudgetAvailabilityCertificate::factory()->for($company)->create([
        'budget_appropriation_id' => $rubro->id,
        'fiscal_year' => 2026,
    ]);
    $thirdParty = ThirdParty::factory()->for($company)->create();
    $rp = BudgetRegistration::factory()->for($company)->create([
        'budget_availability_certificate_id' => $cdp->id,
        'third_party_id' => $thirdParty->id,
        'fiscal_year' => 2026,
    ]);
    $obligation = BudgetObligation::factory()->for($company)->create([
        'budget_registration_id' => $rp->id,
        'fiscal_year' => 2026,
    ]);

    $order = PaymentOrder::factory()->for($company)->create([
        'budget_obligation_id' => $obligation->id,
        'cash_account_id' => $cashAccount->id,
        'issued_on' => now(),
        'number' => null,
    ]);

    $year = now()->format('Y');
    expect($order->number)->toBe("OP-{$year}-000001");
});

it('automatically generates warehouse item code when blank', function () {
    $company = Company::factory()->create();

    $item1 = WarehouseItem::factory()->for($company)->create(['code' => null]);
    $item2 = WarehouseItem::factory()->for($company)->create(['code' => null]);

    expect($item1->code)->toBe('ART-00001')
        ->and($item2->code)->toBe('ART-00002');
});

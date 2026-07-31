<?php

use App\Enums\CashProgramMovementType;
use App\Enums\PaymentOrderStatus;
use App\Enums\UserRole;
use App\Models\BudgetAppropriation;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\BudgetObligation;
use App\Models\BudgetRegistration;
use App\Models\BudgetRevenue;
use App\Models\CashProgramItem;
use App\Models\Company;
use App\Models\IncomeRecord;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->company = Company::factory()->create(['has_budgetary_control' => true]);
    $this->actingAs($this->admin);
});

it('creates a cash program item for an expense rubro', function () {
    $appropriation = BudgetAppropriation::factory()->for($this->company)->create();

    $item = CashProgramItem::factory()->for($this->company)->expense()->create([
        'fiscal_year' => 2026,
        'month' => 3,
        'budget_appropriation_id' => $appropriation->id,
        'projected_amount' => 10_000_000,
    ]);

    expect($item->movement_type)->toBe(CashProgramMovementType::Expense)
        ->and($item->month)->toBe(3)
        ->and((float) $item->projected_amount)->toBe(10_000_000.0);
});

it('calculates executed amount for an expense rubro from payments made in the month', function () {
    $appropriation = BudgetAppropriation::factory()->for($this->company)->create();
    $certificate = BudgetAvailabilityCertificate::factory()->create([
        'company_id' => $this->company->id,
        'budget_appropriation_id' => $appropriation->id,
    ]);
    $registration = BudgetRegistration::factory()->create([
        'company_id' => $this->company->id,
        'budget_availability_certificate_id' => $certificate->id,
    ]);
    $obligation = BudgetObligation::factory()->approved()->create([
        'company_id' => $this->company->id,
        'budget_registration_id' => $registration->id,
    ]);
    $paymentOrder = PaymentOrder::factory()->create([
        'company_id' => $this->company->id,
        'budget_obligation_id' => $obligation->id,
        'status' => PaymentOrderStatus::Paid,
    ]);

    Payment::factory()->create([
        'payment_order_id' => $paymentOrder->id,
        'paid_on' => '2026-03-15',
        'amount' => 4_000_000,
    ]);

    // Pago fuera del mes proyectado, no debe contar.
    Payment::factory()->create([
        'payment_order_id' => $paymentOrder->id,
        'paid_on' => '2026-04-01',
        'amount' => 9_999_999,
    ]);

    $item = CashProgramItem::factory()->for($this->company)->expense()->create([
        'fiscal_year' => 2026,
        'month' => 3,
        'budget_appropriation_id' => $appropriation->id,
        'projected_amount' => 10_000_000,
    ]);

    expect($item->executed_amount)->toBe(4_000_000.0)
        ->and($item->deviation)->toBe(6_000_000.0);
});

it('calculates executed amount for an income rubro from income records in the month', function () {
    $revenue = BudgetRevenue::factory()->for($this->company)->create();

    IncomeRecord::factory()->create([
        'budget_revenue_id' => $revenue->id,
        'accrual_date' => '2026-06-10',
        'amount' => 2_500_000,
    ]);

    // Recaudo fuera del mes proyectado, no debe contar.
    IncomeRecord::factory()->create([
        'budget_revenue_id' => $revenue->id,
        'accrual_date' => '2026-05-10',
        'amount' => 1_000_000,
    ]);

    $item = CashProgramItem::factory()->for($this->company)->income()->create([
        'fiscal_year' => 2026,
        'month' => 6,
        'budget_revenue_id' => $revenue->id,
        'projected_amount' => 3_000_000,
    ]);

    expect($item->executed_amount)->toBe(2_500_000.0)
        ->and($item->deviation)->toBe(500_000.0);
});

it('returns zero executed amount when no rubro is linked', function () {
    $item = CashProgramItem::factory()->for($this->company)->expense()->create([
        'fiscal_year' => 2026,
        'month' => 1,
        'budget_appropriation_id' => null,
        'projected_amount' => 1_000_000,
    ]);

    expect($item->executed_amount)->toBe(0.0);
});

<?php

use App\Enums\UserRole;
use App\Models\BudgetRevenue;
use App\Models\Company;
use App\Models\IncomeRecord;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->company = Company::factory()->create(['has_budgetary_control' => true]);
    $this->actingAs($this->admin);
});

it('creates a budget revenue rubro with projected amount', function () {
    $revenue = BudgetRevenue::factory()->for($this->company)->create([
        'fiscal_year'      => 2026,
        'code'             => '1.1.01',
        'name'             => 'Impuesto Predial',
        'category'         => 'corriente',
        'projected_amount' => 500_000_000,
    ]);

    expect($revenue->code)->toBe('1.1.01')
        ->and($revenue->name)->toBe('Impuesto Predial')
        ->and((float) $revenue->projected_amount)->toBe(500_000_000.0)
        ->and($revenue->fiscal_year)->toBe(2026);
});

it('calculates executed amount from linked income records', function () {
    $revenue = BudgetRevenue::factory()->for($this->company)->create([
        'projected_amount' => 100_000_000,
    ]);

    IncomeRecord::factory()->count(3)->create([
        'budget_revenue_id' => $revenue->id,
        'amount'            => 10_000_000,
    ]);

    $revenue->refresh();

    expect($revenue->executed_amount)->toBe(30_000_000.0);
});

it('calculates execution percentage correctly', function () {
    $revenue = BudgetRevenue::factory()->for($this->company)->create([
        'projected_amount' => 100_000_000,
    ]);

    IncomeRecord::factory()->create([
        'budget_revenue_id' => $revenue->id,
        'amount'            => 75_000_000,
    ]);

    $revenue->refresh();

    expect($revenue->execution_percentage)->toBe(75.0);
});

it('returns 0 execution percentage when no income records exist', function () {
    $revenue = BudgetRevenue::factory()->for($this->company)->create([
        'projected_amount' => 50_000_000,
    ]);

    expect($revenue->executed_amount)->toBe(0.0)
        ->and($revenue->execution_percentage)->toBe(0.0);
});

it('returns 0 execution percentage when projected amount is zero', function () {
    $revenue = BudgetRevenue::factory()->for($this->company)->create([
        'projected_amount' => 0,
    ]);

    expect($revenue->execution_percentage)->toBe(0.0);
});

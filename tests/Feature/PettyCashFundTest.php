<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\PettyCashFund;
use App\Models\PettyCashMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->company = Company::factory()->create();
    $this->actingAs($this->admin);
});

it('creates a petty cash fund', function () {
    $fund = PettyCashFund::factory()->for($this->company)->create([
        'name' => 'Caja Menor Secretaría General',
        'authorized_amount' => 1_000_000,
    ]);

    expect($fund->name)->toBe('Caja Menor Secretaría General')
        ->and((float) $fund->authorized_amount)->toBe(1_000_000.0)
        ->and($fund->is_active)->toBeTrue();
});

it('increases available balance on opening and replenishment', function () {
    $fund = PettyCashFund::factory()->for($this->company)->create();

    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->opening()->create(['amount' => 1_000_000]);
    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->replenishment()->create(['amount' => 300_000]);

    expect($fund->available_balance)->toBe(1_300_000.0);
});

it('decreases available balance on expense and closure', function () {
    $fund = PettyCashFund::factory()->for($this->company)->create();

    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->opening()->create(['amount' => 1_000_000]);
    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->expense()->create(['amount' => 250_000]);
    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->closure()->create(['amount' => 750_000]);

    expect($fund->available_balance)->toBe(0.0);
});

it('reflects the full imprest cycle: open, spend, replenish', function () {
    $fund = PettyCashFund::factory()->for($this->company)->create();

    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->opening()->create(['amount' => 500_000]);
    expect($fund->available_balance)->toBe(500_000.0);

    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->expense()->create(['amount' => 120_000]);
    expect($fund->available_balance)->toBe(380_000.0);

    PettyCashMovement::factory()->for($fund, 'pettyCashFund')->replenishment()->create(['amount' => 120_000]);
    expect($fund->available_balance)->toBe(500_000.0);
});

it('returns zero balance for a fund with no movements', function () {
    $fund = PettyCashFund::factory()->for($this->company)->create();

    expect($fund->available_balance)->toBe(0.0);
});

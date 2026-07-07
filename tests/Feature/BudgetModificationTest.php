<?php

use App\Enums\BudgetModificationType;
use App\Enums\UserRole;
use App\Models\BudgetAppropriation;
use App\Models\BudgetModification;
use App\Models\Company;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->company = Company::factory()->create(['has_budgetary_control' => true]);
    $this->actingAs($this->admin);
});

it('creates a budget modification of type addition and increments the appropriation additions field', function () {
    $appropriation = BudgetAppropriation::factory()->for($this->company)->create([
        'initial_amount' => 10_000_000,
        'additions'      => 0,
    ]);

    $originalAdditions = (float) $appropriation->additions;

    BudgetModification::create([
        'company_id'                   => $this->company->id,
        'type'                         => BudgetModificationType::Addition,
        'document_reference'           => 'Decreto 001 de 2026',
        'source_appropriation_id'      => null,
        'destination_appropriation_id' => $appropriation->id,
        'amount'                       => 2_000_000,
        'effective_date'               => today(),
        'user_id'                      => $this->admin->id,
    ]);

    $appropriation->refresh();

    expect((float) $appropriation->additions)->toBe($originalAdditions + 2_000_000.0)
        ->and($appropriation->total_appropriation)->toBe(12_000_000.0);
});

it('creates a budget modification of type reduction and increments the appropriation reductions field', function () {
    $appropriation = BudgetAppropriation::factory()->for($this->company)->create([
        'initial_amount' => 10_000_000,
        'reductions'     => 0,
    ]);

    BudgetModification::create([
        'company_id'                   => $this->company->id,
        'type'                         => BudgetModificationType::Reduction,
        'document_reference'           => 'Acta de Junta 12',
        'source_appropriation_id'      => null,
        'destination_appropriation_id' => $appropriation->id,
        'amount'                       => 1_500_000,
        'effective_date'               => today(),
        'user_id'                      => $this->admin->id,
    ]);

    $appropriation->refresh();

    expect((float) $appropriation->reductions)->toBe(1_500_000.0)
        ->and($appropriation->total_appropriation)->toBe(8_500_000.0);
});

it('creates a budget modification of type transfer and updates both source and destination appropriations', function () {
    $source = BudgetAppropriation::factory()->for($this->company)->create([
        'initial_amount' => 20_000_000,
        'additions'      => 0,
        'reductions'     => 0,
    ]);

    $destination = BudgetAppropriation::factory()->for($this->company)->create([
        'initial_amount' => 5_000_000,
        'additions'      => 0,
        'reductions'     => 0,
    ]);

    BudgetModification::create([
        'company_id'                   => $this->company->id,
        'type'                         => BudgetModificationType::Transfer,
        'document_reference'           => 'Decreto Traslado 022',
        'source_appropriation_id'      => $source->id,
        'destination_appropriation_id' => $destination->id,
        'amount'                       => 3_000_000,
        'effective_date'               => today(),
        'user_id'                      => $this->admin->id,
    ]);

    $source->refresh();
    $destination->refresh();

    expect((float) $source->reductions)->toBe(3_000_000.0)
        ->and($source->total_appropriation)->toBe(17_000_000.0)
        ->and((float) $destination->additions)->toBe(3_000_000.0)
        ->and($destination->total_appropriation)->toBe(8_000_000.0);
});

it('prevents a transfer when the source appropriation has insufficient balance', function () {
    $source = BudgetAppropriation::factory()->for($this->company)->create([
        'initial_amount' => 1_000_000,
        'additions'      => 0,
        'reductions'     => 0,
    ]);

    $destination = BudgetAppropriation::factory()->for($this->company)->create();

    expect(fn () => BudgetModification::create([
        'company_id'                   => $this->company->id,
        'type'                         => BudgetModificationType::Transfer,
        'document_reference'           => 'Decreto Inválido',
        'source_appropriation_id'      => $source->id,
        'destination_appropriation_id' => $destination->id,
        'amount'                       => 5_000_000,
        'effective_date'               => today(),
        'user_id'                      => $this->admin->id,
    ]))->toThrow(\RuntimeException::class);
});

it('records the user who registered the budget modification', function () {
    $appropriation = BudgetAppropriation::factory()->for($this->company)->create();

    $modification = BudgetModification::create([
        'company_id'                   => $this->company->id,
        'type'                         => BudgetModificationType::Addition,
        'document_reference'           => 'Acuerdo Municipal 5',
        'source_appropriation_id'      => null,
        'destination_appropriation_id' => $appropriation->id,
        'amount'                       => 500_000,
        'effective_date'               => today(),
        'user_id'                      => $this->admin->id,
    ]);

    expect($modification->user_id)->toBe($this->admin->id)
        ->and($modification->user->name)->toBe($this->admin->name);
});

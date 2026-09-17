<?php

use App\Enums\WithholdingType;
use App\Filament\Resources\WithholdingRules\Pages\CreateWithholdingRule;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Municipality;
use App\Models\User;
use App\Models\WithholdingRule;
use App\Services\Accounting\EnsureNoOverlappingIcaRule;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('rejects two ica rules for the same municipio with overlapping vigencia', function () {
    $company = Company::factory()->create();
    $municipality = Municipality::factory()->create();

    WithholdingRule::factory()->for($company)->ica($municipality)->create([
        'starts_on' => '2026-01-01',
        'ends_on' => null,
    ]);

    expect(fn () => app(EnsureNoOverlappingIcaRule::class)->handle($company, $municipality->id, '2026-06-01', null))
        ->toThrow(ValidationException::class);
});

it('allows two ica rules for the same municipio with non-overlapping vigencia', function () {
    $company = Company::factory()->create();
    $municipality = Municipality::factory()->create();

    WithholdingRule::factory()->for($company)->ica($municipality)->create([
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-05-31',
    ]);

    app(EnsureNoOverlappingIcaRule::class)->handle($company, $municipality->id, '2026-06-01', null);
})->throwsNoExceptions();

it('allows two ica rules for different municipios with overlapping vigencia', function () {
    $company = Company::factory()->create();
    $municipalityA = Municipality::factory()->create();
    $municipalityB = Municipality::factory()->create();

    WithholdingRule::factory()->for($company)->ica($municipalityA)->create([
        'starts_on' => '2026-01-01',
        'ends_on' => null,
    ]);

    app(EnsureNoOverlappingIcaRule::class)->handle($company, $municipalityB->id, '2026-06-01', null);
})->throwsNoExceptions();

it('ignores the record being edited when checking for overlap', function () {
    $company = Company::factory()->create();
    $municipality = Municipality::factory()->create();

    $rule = WithholdingRule::factory()->for($company)->ica($municipality)->create([
        'starts_on' => '2026-01-01',
        'ends_on' => null,
    ]);

    app(EnsureNoOverlappingIcaRule::class)->handle($company, $municipality->id, '2026-01-01', null, $rule->id);
})->throwsNoExceptions();

it('surfaces a form validation error when creating an overlapping ica rule via the resource', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create();
    $municipality = Municipality::factory()->create();
    $chartAccount = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '236510']);

    WithholdingRule::factory()->for($company)->ica($municipality)->create([
        'starts_on' => '2026-01-01',
        'ends_on' => null,
    ]);

    Livewire::test(CreateWithholdingRule::class)
        ->fillForm([
            'company_id' => $company->id,
            'type' => WithholdingType::Ica->value,
            'municipality_id' => $municipality->id,
            'chart_account_id' => $chartAccount->id,
            'minimum_base' => 0,
            'rate' => 0.966,
            'starts_on' => '2026-06-01',
            'ends_on' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasErrors(['starts_on']);

    expect(WithholdingRule::query()->where('company_id', $company->id)->count())->toBe(1);
});

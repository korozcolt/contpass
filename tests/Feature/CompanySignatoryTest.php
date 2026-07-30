<?php

use App\Enums\SignatoryArea;
use App\Filament\Resources\CompanySignatories\Pages\CreateCompanySignatory;
use App\Filament\Resources\CompanySignatories\Pages\EditCompanySignatory;
use App\Models\Company;
use App\Models\CompanySignatory;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create a company signatory', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();

    Livewire::test(CreateCompanySignatory::class)
        ->fillForm([
            'company_id' => $company->id,
            'area' => SignatoryArea::Treasury->value,
            'full_name' => 'María Pérez',
            'position' => 'Tesorera',
            'identification' => '1234567890',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(CompanySignatory::class, [
        'company_id' => $company->id,
        'area' => SignatoryArea::Treasury->value,
        'full_name' => 'María Pérez',
    ]);
});

it('casts the area to the signatory enum when editing', function () {
    $this->actingAs(User::factory()->create());
    $signatory = CompanySignatory::factory()->create(['area' => SignatoryArea::Budget]);

    Livewire::test(EditCompanySignatory::class, ['record' => $signatory->getKey()])
        ->assertSchemaStateSet([
            'area' => SignatoryArea::Budget,
            'full_name' => $signatory->full_name,
        ]);

    expect($signatory->fresh()->area)->toBe(SignatoryArea::Budget);
});

<?php

use App\Enums\CompanyType;
use App\Filament\Pages\CompanySettings;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

it('can store company profile fields and casts enum', function () {
    $company = Company::factory()->create([
        'type' => CompanyType::Private,
    ]);

    $company->update([
        'type' => CompanyType::Public,
        'phone' => '3001234567',
        'email' => 'contacto@empresa.com',
        'address' => 'Calle 10 # 5-20',
        'city' => 'Sincelejo',
        'legal_representative' => 'Juan Perez',
    ]);

    $fresh = $company->fresh();

    expect($fresh->type)->toBe(CompanyType::Public)
        ->and($fresh->phone)->toBe('3001234567')
        ->and($fresh->email)->toBe('contacto@empresa.com')
        ->and($fresh->address)->toBe('Calle 10 # 5-20')
        ->and($fresh->city)->toBe('Sincelejo')
        ->and($fresh->legal_representative)->toBe('Juan Perez');
});

it('defaults to private type when newly created', function () {
    $company = Company::query()->create([
        'name' => 'Nueva Empresa',
        'tax_id' => '123456789',
        'verification_digit' => 0,
        'currency' => 'COP',
    ]);

    expect($company->type)->toBe(CompanyType::Private);
});

it('renders and saves company settings via livewire page', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $company = Company::query()->firstOrCreate(
        ['tax_id' => '900000000'],
        [
            'name' => 'Empresa Principal',
            'verification_digit' => 9,
            'currency' => 'COP',
        ]
    );

    Livewire::test(CompanySettings::class)
        ->assertFormSet([
            'name' => 'Empresa Principal',
            'tax_id' => '900000000',
        ])
        ->fillForm([
            'name' => 'Empresa Modificada',
            'type' => CompanyType::Public->value,
            'phone' => '555-9999',
            'email' => 'admin@empresa.com',
            'has_budgetary_control' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $company->refresh();

    expect($company->name)->toBe('Empresa Modificada')
        ->and($company->type)->toBe(CompanyType::Public)
        ->and($company->phone)->toBe('555-9999')
        ->and($company->email)->toBe('admin@empresa.com')
        ->and($company->has_budgetary_control)->toBeTrue();
});

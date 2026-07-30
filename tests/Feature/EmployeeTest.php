<?php

use App\Enums\EmployeeContractType;
use App\Enums\PayrollFundType;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Models\Company;
use App\Models\Dependency;
use App\Models\Employee;
use App\Models\PayrollFund;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create an employee with dependency and funds', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();
    $dependency = Dependency::factory()->for($company)->create();
    $pensionFund = PayrollFund::factory()->for($company)->create(['type' => PayrollFundType::Pension]);
    $healthFund = PayrollFund::factory()->for($company)->create(['type' => PayrollFundType::Health]);

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'company_id' => $company->id,
            'name' => 'Juan Pérez',
            'position' => 'Auxiliar Administrativo',
            'tax_id' => '1234567890',
            'verification_digit' => 5,
            'dependency_id' => $dependency->id,
            'contract_type' => EmployeeContractType::Indefinite->value,
            'pension_fund_id' => $pensionFund->id,
            'health_fund_id' => $healthFund->id,
            'hire_date' => '2026-01-15',
            'base_salary' => 2500000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Employee::class, [
        'company_id' => $company->id,
        'name' => 'Juan Pérez',
        'tax_id' => '1234567890',
        'dependency_id' => $dependency->id,
        'pension_fund_id' => $pensionFund->id,
        'health_fund_id' => $healthFund->id,
        'contract_type' => EmployeeContractType::Indefinite->value,
    ]);
});

it('casts the contract type to the employee enum when editing', function () {
    $this->actingAs(User::factory()->create());
    $employee = Employee::factory()->create(['contract_type' => EmployeeContractType::FixedTerm]);

    Livewire::test(EditEmployee::class, ['record' => $employee->getKey()])
        ->assertSchemaStateSet([
            'contract_type' => EmployeeContractType::FixedTerm,
            'name' => $employee->name,
        ]);

    expect($employee->fresh()->contract_type)->toBe(EmployeeContractType::FixedTerm);
});

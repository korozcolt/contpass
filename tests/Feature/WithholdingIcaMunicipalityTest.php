<?php

use App\Enums\WithholdingType;
use App\Filament\Resources\ExpenseRecords\Pages\CreateExpenseRecord;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\ThirdParty;
use App\Models\User;
use App\Models\WithholdingRule;
use App\Services\Accounting\ApplyWithholdingRules;
use App\Services\Accounting\PostExpenseVoucher;
use Livewire\Livewire;

function icaMunicipalityFixture(): array
{
    $company = Company::factory()->create();
    $withholdingAccount = ChartAccount::factory()->credit()->create(['company_id' => $company->id]);
    $municipioA = Municipality::factory()->create();
    $municipioB = Municipality::factory()->create();
    $municipioC = Municipality::factory()->create();

    $ruleA = WithholdingRule::factory()->ica($municipioA)->create([
        'company_id' => $company->id,
        'chart_account_id' => $withholdingAccount->id,
        'rate' => 2,
        'starts_on' => '2026-01-01',
    ]);

    $ruleB = WithholdingRule::factory()->ica($municipioB)->create([
        'company_id' => $company->id,
        'chart_account_id' => $withholdingAccount->id,
        'rate' => 5,
        'starts_on' => '2026-01-01',
    ]);

    return compact('company', 'withholdingAccount', 'municipioA', 'municipioB', 'municipioC', 'ruleA', 'ruleB');
}

it('applies only the ica rule for municipio A when municipio A is passed', function () {
    ['company' => $company, 'municipioA' => $municipioA, 'ruleA' => $ruleA] = icaMunicipalityFixture();

    $withholdings = app(ApplyWithholdingRules::class)->handle($company, 100000, '2026-07-01', $municipioA->id);

    expect($withholdings)->toHaveCount(1)
        ->and($withholdings->first()['rule']->id)->toBe($ruleA->id);
});

it('applies only the ica rule for municipio B when municipio B is passed', function () {
    ['company' => $company, 'municipioB' => $municipioB, 'ruleB' => $ruleB] = icaMunicipalityFixture();

    $withholdings = app(ApplyWithholdingRules::class)->handle($company, 100000, '2026-07-01', $municipioB->id);

    expect($withholdings)->toHaveCount(1)
        ->and($withholdings->first()['rule']->id)->toBe($ruleB->id);
});

it('applies no ica withholding when the municipio has no matching rule', function () {
    ['company' => $company, 'municipioC' => $municipioC] = icaMunicipalityFixture();

    $withholdings = app(ApplyWithholdingRules::class)->handle($company, 100000, '2026-07-01', $municipioC->id);

    expect($withholdings)->toHaveCount(0);
});

it('never filters rete fuente rules by municipality', function () {
    ['company' => $company, 'withholdingAccount' => $withholdingAccount, 'municipioA' => $municipioA] = icaMunicipalityFixture();

    WithholdingRule::factory()->create([
        'company_id' => $company->id,
        'chart_account_id' => $withholdingAccount->id,
        'type' => WithholdingType::ReteFuente,
        'municipality_id' => null,
        'rate' => 4,
        'starts_on' => '2026-01-01',
    ]);

    $withholdings = app(ApplyWithholdingRules::class)->handle($company, 100000, '2026-07-01', $municipioA->id);

    expect($withholdings)->toHaveCount(2)
        ->and($withholdings->pluck('rule.type')->map(fn (WithholdingType $type) => $type->value)->sort()->values()->all())
        ->toBe(['ica', 'rete_fuente']);
});

it('applies zero ica withholdings when no municipality is supplied at all', function () {
    ['company' => $company] = icaMunicipalityFixture();

    $withholdings = app(ApplyWithholdingRules::class)->handle($company, 100000, '2026-07-01');

    expect($withholdings)->toHaveCount(0);
});

it('applies only the matching municipio ica rule end-to-end through PostExpenseVoucher, leaving ReteFuente unaffected', function () {
    ['company' => $company, 'withholdingAccount' => $withholdingAccount, 'municipioA' => $municipioA] = icaMunicipalityFixture();

    WithholdingRule::factory()->create([
        'company_id' => $company->id,
        'chart_account_id' => $withholdingAccount->id,
        'type' => WithholdingType::ReteFuente,
        'municipality_id' => null,
        'rate' => 4,
        'starts_on' => '2026-01-01',
    ]);

    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id]);
    $expenseAccount = ChartAccount::factory()->create(['company_id' => $company->id]);
    $payableAccount = ChartAccount::factory()->credit()->create(['company_id' => $company->id]);

    $voucher = app(PostExpenseVoucher::class)->handle($company, $thirdParty, [
        'third_party_id' => $thirdParty->id,
        'expense_account_id' => $expenseAccount->id,
        'payable_account_id' => $payableAccount->id,
        'municipality_id' => $municipioA->id,
        'support_type' => 'Cuenta de cobro',
        'support_number' => 'CC-100',
        'accrual_date' => '2026-07-01',
        'amount' => 100000,
        'has_valid_support' => true,
        'is_deductible' => true,
    ]);

    $expenseRecord = $voucher->expenseRecord;

    expect($voucher->isBalanced())->toBeTrue()
        ->and($expenseRecord->municipality_id)->toBe($municipioA->id)
        ->and((float) $expenseRecord->withholding_amount)->toBe(6000.0);
});

it('defaults the expense record municipality field from the company domicile and allows manual override', function () {
    $department = Department::factory()->create(['code' => '11']);
    $municipioA = Municipality::factory()->create(['department_id' => $department->id, 'code' => '001']);
    $otherMunicipality = Municipality::factory()->create();

    Company::factory()->create([
        'dane_department_code' => $department->code,
        'dane_municipality_code' => $municipioA->code,
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(CreateExpenseRecord::class)
        ->assertFormSet(['municipality_id' => $municipioA->id])
        ->fillForm(['municipality_id' => $otherMunicipality->id])
        ->assertHasNoFormErrors();
});

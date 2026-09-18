<?php

use App\Enums\AccountNature;
use App\Http\Controllers\AccountingReportController;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\PostIncomeVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @param  array<int, mixed>  $args
 */
function invokePrivateMethod(object $object, string $method, array $args = []): mixed
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($object, ...$args);
}

function rowBuilderFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000004']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373914', 'verification_digit' => 5]);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos operacionales']);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);

    app(PostIncomeVoucher::class)->handle($company, $thirdParty, [
        'third_party_id' => $thirdParty->id,
        'revenue_account_id' => $revenue->id,
        'receivable_account_id' => $receivable->id,
        'support_number' => 'REC-300',
        'accrual_date' => now()->toDateString(),
        'amount' => 250000,
    ]);

    return compact('company', 'thirdParty');
}

it('ledgerRows casts debit and credit to float', function () {
    rowBuilderFixture();

    $rows = invokePrivateMethod(app(AccountingReportController::class), 'ledgerRows', [Request::create('/accounting-reports/ledger')]);

    expect($rows)->toHaveCount(1);

    $row = $rows[0];

    expect($row[0])->toBeInstanceOf(Carbon::class)
        ->and($row[5])->toBeFloat()
        ->and($row[6])->toBeFloat();
});

it('thirdPartyMovementsRows casts debit and credit to float', function () {
    rowBuilderFixture();

    $rows = invokePrivateMethod(app(AccountingReportController::class), 'thirdPartyMovementsRows', [Request::create('/accounting-reports/third-party-movements')]);

    expect($rows)->toHaveCount(2);

    $row = $rows[0];

    expect($row[0])->toBeInstanceOf(Carbon::class)
        ->and($row[5])->toBeFloat()
        ->and($row[6])->toBeFloat();
});

it('existing CSV export behavior is unchanged after the refactor', function () {
    $this->actingAs(User::factory()->create());
    rowBuilderFixture();

    $this->get(route('accounting-reports.ledger', ['export' => 1]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('all 6 shared row-builder methods exist as private methods returning an array', function () {
    foreach (['ledgerRows', 'trialBalanceRows', 'thirdPartyMovementsRows', 'generalLedgerRows', 'accountsReceivableRows', 'accountsPayableRows'] as $method) {
        $reflection = new ReflectionMethod(AccountingReportController::class, $method);

        expect($reflection->isPrivate())->toBeTrue();
        expect((string) $reflection->getReturnType())->toBe('array');
    }
});

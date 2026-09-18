<?php

use App\Enums\AccountNature;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\PostIncomeVoucher;
use OpenSpout\Reader\XLSX\Reader;

function xlsxExportFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000005']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id, 'tax_id' => '900373914', 'verification_digit' => 5]);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'name' => 'Ingresos operacionales']);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'name' => 'Clientes', 'nature' => AccountNature::Debit]);

    app(PostIncomeVoucher::class)->handle($company, $thirdParty, [
        'third_party_id' => $thirdParty->id,
        'revenue_account_id' => $revenue->id,
        'receivable_account_id' => $receivable->id,
        'support_number' => 'REC-400',
        'accrual_date' => '2026-05-20',
        'amount' => 750000,
    ]);

    return compact('company', 'thirdParty');
}

$xlsxRoutes = [
    'ledger' => 'accounting-reports.ledger.xlsx',
    'trial-balance' => 'accounting-reports.trial-balance.xlsx',
    'third-party-movements' => 'accounting-reports.third-party-movements.xlsx',
    'general-ledger' => 'accounting-reports.general-ledger.xlsx',
    'accounts-receivable' => 'accounting-reports.accounts-receivable.xlsx',
    'accounts-payable' => 'accounting-reports.accounts-payable.xlsx',
];

it('blocks the .xlsx export for guests', function (string $routeName) use ($xlsxRoutes) {
    $this->get(route($xlsxRoutes[$routeName]))->assertForbidden();
})->with(array_keys($xlsxRoutes));

it('exports the report as an authenticated .xlsx download', function (string $routeName) use ($xlsxRoutes) {
    $this->actingAs(User::factory()->create());
    xlsxExportFixture();

    $this->get(route($xlsxRoutes[$routeName]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
})->with(array_keys($xlsxRoutes));

it('writes the ledger date and debit columns as native typed cells, not text', function () {
    $this->actingAs(User::factory()->create());
    xlsxExportFixture();

    $response = $this->get(route('accounting-reports.ledger.xlsx'))->assertSuccessful();

    $tempPath = tempnam(sys_get_temp_dir(), 'ledger-xlsx-').'.xlsx';
    file_put_contents($tempPath, $response->streamedContent());

    $reader = new Reader();
    $reader->open($tempPath);

    $dataRow = null;
    foreach ($reader->getSheetIterator() as $sheet) {
        $rowIndex = 0;
        foreach ($sheet->getRowIterator() as $row) {
            if ($rowIndex === 1) {
                $dataRow = $row->toArray();
            }
            $rowIndex++;
        }
        break;
    }
    $reader->close();
    unlink($tempPath);

    expect($dataRow)->not->toBeNull()
        ->and($dataRow[0])->toBeInstanceOf(DateTimeInterface::class)
        ->and(is_float($dataRow[5]) || is_int($dataRow[5]))->toBeTrue();
});

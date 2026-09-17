<?php

use App\Enums\BankProfile;
use App\Enums\BankStatementLineStatus;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\CashAccount;
use App\Services\Accounting\ImportBankStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array<int, string>>  $rows
 */
function importBankStatementCsvFixture(array $rows, string $delimiter = ',', string $encoding = 'UTF-8'): string
{
    $path = tempnam(sys_get_temp_dir(), 'bankstmt_').'.csv';

    $lines = array_map(
        fn (array $row): string => implode($delimiter, $row),
        $rows,
    );

    $content = implode("\r\n", $lines)."\r\n";

    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, $encoding, 'UTF-8');
    }

    file_put_contents($path, $content);

    return $path;
}

it('imports a valid comma-delimited Bancolombia CSV and derives the import period', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
        ['10/09/2026', 'Transferencia', '75000', 'REF3'],
    ]);

    $import = app(ImportBankStatement::class)->handle(
        $cashAccount->company,
        $cashAccount,
        BankProfile::Bancolombia,
        $path,
    );

    expect($import)->toBeInstanceOf(BankStatementImport::class)
        ->and($import->starts_on->toDateString())->toBe('2026-09-01')
        ->and($import->ends_on->toDateString())->toBe('2026-09-10');

    unlink($path);
});

it('auto-detects the semicolon delimiter (D-03)', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
    ], delimiter: ';');

    $import = app(ImportBankStatement::class)->handle(
        $cashAccount->company,
        $cashAccount,
        BankProfile::Bancolombia,
        $path,
    );

    expect($import->starts_on->toDateString())->toBe('2026-09-01')
        ->and($import->ends_on->toDateString())->toBe('2026-09-05');

    unlink($path);
});

it('rejects the whole file when required column headers are not recognizable (D-03)', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripcion'],
        ['01/09/2026', 'Pago recibido'],
    ]);

    $importsBefore = BankStatementImport::count();

    try {
        app(ImportBankStatement::class)->handle(
            $cashAccount->company,
            $cashAccount,
            BankProfile::Bancolombia,
            $path,
        );

        test()->fail('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->validator->errors()->first('file'))
            ->toBe('El archivo no tiene encabezados de columna reconocibles. Verifica que sea un CSV exportado del banco seleccionado y vuelve a intentarlo.');
    }

    expect(BankStatementImport::count())->toBe($importsBefore);

    unlink($path);
});

it('rejects the whole file when its date range overlaps a previous import for the same cash account (D-04/D-05)', function () {
    $cashAccount = CashAccount::factory()->create();

    $firstPath = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
    ]);

    app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $firstPath);

    $secondPath = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['05/09/2026', 'Otro pago', '20000', 'REF3'],
        ['08/09/2026', 'Otro pago mas', '30000', 'REF4'],
    ]);

    $importsBefore = BankStatementImport::count();
    $linesBefore = BankStatementLine::count();

    try {
        app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $secondPath);

        test()->fail('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->validator->errors()->first('file'))->toContain('se solapa');
    }

    expect(BankStatementImport::count())->toBe($importsBefore)
        ->and(BankStatementLine::count())->toBe($linesBefore);

    unlink($firstPath);
    unlink($secondPath);
});

it('allows the same overlapping date range for a different cash account (solape es por cuenta, no global)', function () {
    $cashAccount = CashAccount::factory()->create();
    $otherCashAccount = CashAccount::factory()->create();

    $firstPath = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
    ]);

    app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $firstPath);

    $secondPath = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
    ]);

    $import = app(ImportBankStatement::class)->handle($otherCashAccount->company, $otherCashAccount, BankProfile::Bancolombia, $secondPath);

    expect($import)->toBeInstanceOf(BankStatementImport::class)
        ->and(BankStatementImport::count())->toBe(2);

    unlink($firstPath);
    unlink($secondPath);
});

it('rejects an individual row with an unparseable date without aborting the rest of the file (BANKREC-06)', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
        ['31/13/2026', 'Fecha invalida', '10000', 'REF3'],
        ['10/09/2026', 'Transferencia', '75000', 'REF4'],
    ]);

    $import = app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $path);

    $lines = $import->lines;

    expect($lines)->toHaveCount(4)
        ->and($lines->where('status', BankStatementLineStatus::Pending)->count())->toBe(3)
        ->and($lines->where('status', BankStatementLineStatus::Rejected)->count())->toBe(1);

    $rejected = $lines->firstWhere('status', BankStatementLineStatus::Rejected);

    expect($rejected->reject_reason)->toContain('fecha');

    unlink($path);
});

it('rejects an individual row with a non-numeric amount without aborting the rest of the file', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Monto invalido', 'N/D', 'REF2'],
        ['10/09/2026', 'Transferencia', '75000', 'REF3'],
    ]);

    $import = app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $path);

    expect($import->lines)->toHaveCount(3);

    $rejected = $import->lines->firstWhere('status', BankStatementLineStatus::Rejected);

    expect($rejected)->not->toBeNull()
        ->and($rejected->reject_reason)->toContain('monto');

    unlink($path);
});

it('converts Windows-1252 encoding to UTF-8 automatically (BANKREC-02)', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripcion', 'Valor', 'Referencia'],
        ['01/09/2026', 'Depósito en efectivo', '100000', 'REF1'],
    ], encoding: 'Windows-1252');

    $import = app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $path);

    expect($import->lines->first()->description)->toBe('Depósito en efectivo');

    unlink($path);
});

it('normalizes a thousands-separated comma-decimal amount (150.000,50 -> 150000.50)', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '150.000,50', 'REF1'],
    ]);

    $import = app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $path);

    expect((float) $import->lines->first()->amount)->toBe(150000.50);

    unlink($path);
});

it('persists the raw original row for every line, valid or rejected (Core Value traceability)', function () {
    $cashAccount = CashAccount::factory()->create();

    $path = importBankStatementCsvFixture([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['31/13/2026', 'Fecha invalida', '10000', 'REF2'],
    ]);

    $import = app(ImportBankStatement::class)->handle($cashAccount->company, $cashAccount, BankProfile::Bancolombia, $path);

    $import->lines->each(function (BankStatementLine $line) {
        expect($line->raw_row)->toBeArray()->not->toBeEmpty();
    });

    $rejected = $import->lines->firstWhere('status', BankStatementLineStatus::Rejected);

    expect($rejected->raw_row)->toMatchArray(['Referencia' => 'REF2']);

    unlink($path);
});

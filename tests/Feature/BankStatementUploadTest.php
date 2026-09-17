<?php

use App\Enums\BankProfile;
use App\Filament\Pages\UploadBankStatement;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\CashAccount;
use App\Models\User;
use App\Services\Accounting\CurrentCompany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * @param  array<int, array<int, string>>  $rows
 */
function uploadBankStatementCsvContent(array $rows): string
{
    $handle = fopen('php://temp', 'w+');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    rewind($handle);
    $content = (string) stream_get_contents($handle);
    fclose($handle);

    return $content;
}

it('uploads a valid Bancolombia CSV, creates the import with its lines, and redirects to the review page', function () {
    Storage::fake('local');

    $cashAccount = CashAccount::factory()->create();
    mock(CurrentCompany::class)->shouldReceive('get')->andReturn($cashAccount->company);

    $this->actingAs(User::factory()->create());

    $csv = uploadBankStatementCsvContent([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['01/09/2026', 'Pago recibido', '100000', 'REF1'],
        ['05/09/2026', 'Consignación', '50000', 'REF2'],
    ]);

    Livewire::test(UploadBankStatement::class)
        ->fillForm([
            'cash_account_id' => $cashAccount->id,
            'bank' => BankProfile::Bancolombia->value,
            'file' => [UploadedFile::fake()->createWithContent('extracto.csv', $csv)],
        ])
        ->call('import')
        ->assertHasNoFormErrors();

    expect(BankStatementImport::count())->toBe(1)
        ->and(BankStatementLine::count())->toBe(2);
});

it('shows the exact D-03 error when the CSV has no recognizable headers and creates nothing', function () {
    Storage::fake('local');

    $cashAccount = CashAccount::factory()->create();
    mock(CurrentCompany::class)->shouldReceive('get')->andReturn($cashAccount->company);

    $this->actingAs(User::factory()->create());

    $csv = uploadBankStatementCsvContent([
        ['Fecha', 'Descripcion'],
        ['01/09/2026', 'Pago recibido'],
    ]);

    Livewire::test(UploadBankStatement::class)
        ->fillForm([
            'cash_account_id' => $cashAccount->id,
            'bank' => BankProfile::Bancolombia->value,
            'file' => [UploadedFile::fake()->createWithContent('extracto.csv', $csv)],
        ])
        ->call('import')
        ->assertHasFormErrors([
            'file' => 'El archivo no tiene encabezados de columna reconocibles. Verifica que sea un CSV exportado del banco seleccionado y vuelve a intentarlo.',
        ]);

    expect(BankStatementImport::count())->toBe(0);
});

it('shows the D-05 overlap error when the CSV date range overlaps a previous import for the same cash account', function () {
    Storage::fake('local');

    $cashAccount = CashAccount::factory()->create();
    mock(CurrentCompany::class)->shouldReceive('get')->andReturn($cashAccount->company);

    BankStatementImport::factory()->create([
        'cash_account_id' => $cashAccount->id,
        'starts_on' => '2026-09-01',
        'ends_on' => '2026-09-10',
    ]);

    $this->actingAs(User::factory()->create());

    $csv = uploadBankStatementCsvContent([
        ['Fecha', 'Descripción', 'Valor', 'Referencia'],
        ['05/09/2026', 'Otro pago', '20000', 'REF3'],
        ['08/09/2026', 'Otro pago mas', '30000', 'REF4'],
    ]);

    $importsBefore = BankStatementImport::count();

    $component = Livewire::test(UploadBankStatement::class)
        ->fillForm([
            'cash_account_id' => $cashAccount->id,
            'bank' => BankProfile::Bancolombia->value,
            'file' => [UploadedFile::fake()->createWithContent('extracto.csv', $csv)],
        ])
        ->call('import')
        ->assertHasFormErrors(['file']);

    expect($component->errors()->first('data.file'))->toContain('se solapa')
        ->and(BankStatementImport::count())->toBe($importsBefore);
});

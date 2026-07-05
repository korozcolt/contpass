<?php

use App\Models\Company;
use App\Models\Voucher;
use Illuminate\Support\Facades\File;

test('archive master preview import supports dry run and persists only balanced vouchers', function (): void {
    $path = storage_path('app/testing/archive-master-preview.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        'schema_version' => 'contpass.import.v1-preview',
        'currency' => 'COP',
        'company' => [
            'name' => 'Empresa Demo S.A.S.',
        ],
        'chart_accounts' => [
            ['code' => '5110', 'name' => 'Honorarios'],
            ['code' => '1110050101', 'name' => 'Banco principal'],
        ],
        'third_parties' => [
            ['id_number' => '900111222', 'name' => 'Empresa Demo S.A.S.', 'legal_type' => 'legal_entity'],
            ['id_number' => '100200300', 'name' => 'Proveedor Demo', 'legal_type' => 'unknown'],
        ],
        'vouchers' => [
            [
                'id' => 'am-doc-1',
                'source_document' => ['archive_master_id' => 1, 'archive_master_number' => 'DOC-1'],
                'voucher_type' => 'egreso',
                'number' => 'CE-001',
                'date' => '2026-01-15',
                'third_party_id_number' => '100200300',
                'third_party_name' => 'Proveedor Demo',
                'concept' => 'Pago de honorarios',
                'total' => 100000,
                'lines' => [
                    ['account_code' => '5110', 'description' => 'Honorarios', 'debit' => 100000, 'credit' => 0],
                    ['account_code' => '1110050101', 'description' => 'Banco principal', 'debit' => 0, 'credit' => 100000],
                ],
                'validation' => ['balanced' => true],
            ],
            [
                'id' => 'am-doc-2',
                'source_document' => ['archive_master_id' => 2, 'archive_master_number' => 'DOC-2'],
                'voucher_type' => 'egreso',
                'number' => 'CE-002',
                'date' => '15/01/2026',
                'third_party_id_number' => '100200300',
                'concept' => 'Pago desbalanceado',
                'total' => 90000,
                'lines' => [
                    ['account_code' => '5110', 'description' => 'Honorarios', 'debit' => 100000, 'credit' => 0],
                    ['account_code' => '1110050101', 'description' => 'Banco principal', 'debit' => 0, 'credit' => 90000],
                ],
                'validation' => ['balanced' => false],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('contpass:import-archive-master-preview', ['path' => $path])
        ->assertSuccessful();

    expect(Company::query()->count())->toBe(0);

    $this->artisan('contpass:import-archive-master-preview', ['path' => $path, '--commit' => true])
        ->assertSuccessful();

    expect(Company::query()->where('tax_id', '900111222')->exists())->toBeTrue()
        ->and(Voucher::query()->where('number', 'AM-1-CE-001')->exists())->toBeTrue()
        ->and(Voucher::query()->where('number', 'AM-2-CE-002')->exists())->toBeFalse();

    $this->artisan('contpass:import-archive-master-preview', ['path' => $path, '--commit' => true])
        ->assertSuccessful();

    expect(Voucher::query()->where('number', 'AM-1-CE-001')->count())->toBe(1);
});

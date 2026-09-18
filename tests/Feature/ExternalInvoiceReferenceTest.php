<?php

use App\Models\ExternalInvoiceReference;
use App\Models\IncomeRecord;
use Illuminate\Database\QueryException;

it('un IncomeRecord sin referencia devuelve null en externalInvoiceReference', function () {
    $incomeRecord = IncomeRecord::factory()->create();

    expect($incomeRecord->externalInvoiceReference)->toBeNull();
});

it('un IncomeRecord puede tener una ExternalInvoiceReference asociada vía hasOne', function () {
    $incomeRecord = IncomeRecord::factory()->create();

    ExternalInvoiceReference::factory()->for($incomeRecord)->create(['invoice_number' => 'FE-100']);

    expect($incomeRecord->fresh()->externalInvoiceReference->invoice_number)->toBe('FE-100');
});

it('income_record_id es único a nivel de base de datos', function () {
    $incomeRecord = IncomeRecord::factory()->create();

    ExternalInvoiceReference::factory()->for($incomeRecord)->create(['invoice_number' => 'FE-100']);

    expect(fn () => ExternalInvoiceReference::factory()->create([
        'income_record_id' => $incomeRecord->id,
        'invoice_number' => 'FE-200',
    ]))->toThrow(QueryException::class);
});

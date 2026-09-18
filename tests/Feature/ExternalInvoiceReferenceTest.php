<?php

use App\Filament\Resources\IncomeRecords\Pages\ListIncomeRecords;
use App\Models\ExternalInvoiceReference;
use App\Models\IncomeRecord;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

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

it('registra una referencia de factura externa sobre un IncomeRecord existente', function () {
    $this->actingAs(User::factory()->create());
    $incomeRecord = IncomeRecord::factory()->create();

    Livewire::test(ListIncomeRecords::class)
        ->callAction(TestAction::make('external_invoice')->table($incomeRecord), [
            'invoice_number' => 'FE-001',
            'cufe' => null,
            'provider' => 'Proveedor X',
            'document_url' => null,
        ]);

    expect($incomeRecord->fresh()->externalInvoiceReference->invoice_number)->toBe('FE-001');
});

it('exige invoice_number para registrar la factura externa', function () {
    $this->actingAs(User::factory()->create());
    $incomeRecord = IncomeRecord::factory()->create();

    Livewire::test(ListIncomeRecords::class)
        ->callAction(TestAction::make('external_invoice')->table($incomeRecord), [
            'invoice_number' => '',
        ])
        ->assertHasFormErrors(['invoice_number' => 'required']);
});

it('permite editar una referencia existente reenviando el mismo invoice_number sin error de unicidad', function () {
    $this->actingAs(User::factory()->create());
    $incomeRecord = IncomeRecord::factory()->create();
    ExternalInvoiceReference::factory()->for($incomeRecord)->create(['invoice_number' => 'FE-777']);

    Livewire::test(ListIncomeRecords::class)
        ->callAction(TestAction::make('external_invoice')->table($incomeRecord), [
            'invoice_number' => 'FE-777',
            'provider' => 'Proveedor Actualizado',
        ])
        ->assertHasNoFormErrors();

    expect($incomeRecord->fresh()->externalInvoiceReference->provider)->toBe('Proveedor Actualizado');
});

it('el modal se pre-rellena con los datos existentes al editar', function () {
    $this->actingAs(User::factory()->create());
    $incomeRecord = IncomeRecord::factory()->create();
    ExternalInvoiceReference::factory()->for($incomeRecord)->create(['invoice_number' => 'FE-777']);

    Livewire::test(ListIncomeRecords::class)
        ->mountAction(TestAction::make('external_invoice')->table($incomeRecord))
        ->assertSchemaStateSet(['invoice_number' => 'FE-777']);
});

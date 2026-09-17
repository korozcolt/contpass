<?php

use App\Enums\BankProfile;
use App\Enums\BankStatementLineStatus;
use App\Enums\BankStatementMatchConfidence;
use App\Enums\BankStatementMatchStatus;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankStatementMatch;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('fixes labels and colors for the bank reconciliation enums', function () {
    foreach (BankStatementLineStatus::cases() as $case) {
        expect($case->getLabel())->not->toBeEmpty()
            ->and($case->getColor())->not->toBeEmpty();
    }

    foreach (BankStatementMatchConfidence::cases() as $case) {
        expect($case->getLabel())->not->toBeEmpty()
            ->and($case->getColor())->not->toBeEmpty();
    }

    expect(BankStatementMatchConfidence::High->getColor())->toBe('success')
        ->and(BankStatementMatchConfidence::Candidate->getColor())->toBe('gray')
        ->and(BankProfile::Bancolombia->getLabel())->toBe('Bancolombia')
        ->and(BankProfile::Davivienda->getLabel())->toBe('Davivienda');

    foreach (BankStatementMatchStatus::cases() as $case) {
        expect($case->getLabel())->not->toBeEmpty();
    }
});

it('fixes the migration columns for the bank reconciliation schema', function () {
    expect(Schema::hasColumns('bank_statement_imports', [
        'company_id', 'cash_account_id', 'bank', 'file_name', 'starts_on', 'ends_on', 'imported_by',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('bank_statement_lines', [
            'bank_statement_import_id', 'line_date', 'description', 'amount', 'reference', 'raw_row', 'status', 'reject_reason',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('bank_statement_matches', [
            'bank_statement_line_id', 'confidence', 'status', 'confirmed_by', 'confirmed_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('bank_statement_match_payment', [
            'bank_statement_match_id', 'payment_id',
        ]))->toBeTrue();
});

it('relates a bank statement import to its lines', function () {
    $import = BankStatementImport::factory()->has(BankStatementLine::factory()->count(2), 'lines')->create();

    expect($import->lines)->toHaveCount(2)
        ->and($import->lines->first())->toBeInstanceOf(BankStatementLine::class);

    $line = BankStatementLine::factory()->create();

    expect($line->import)->toBeInstanceOf(BankStatementImport::class);
});

it('relates a bank statement line to its matches', function () {
    $line = BankStatementLine::factory()->has(BankStatementMatch::factory(), 'matches')->create();

    expect($line->matches->first())->toBeInstanceOf(BankStatementMatch::class);

    $match = BankStatementMatch::factory()->create();

    expect($match->line)->toBeInstanceOf(BankStatementLine::class);
});

it('relates a bank statement match to payments via the pivot', function () {
    $match = BankStatementMatch::factory()->create();
    $payment = Payment::factory()->create();

    $match->payments()->attach($payment);

    expect($match->payments)->toHaveCount(1)
        ->and($match->payments->first())->toBeInstanceOf(Payment::class);

    $this->assertDatabaseHas('bank_statement_match_payment', [
        'bank_statement_match_id' => $match->id,
        'payment_id' => $payment->id,
    ]);
});

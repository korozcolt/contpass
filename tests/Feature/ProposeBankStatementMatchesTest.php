<?php

use App\Enums\BankStatementMatchConfidence;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\CashAccount;
use App\Models\Payment;
use App\Services\Accounting\ProposeBankStatementMatches;

function proposeMatchesFixture(): array
{
    $cashAccount = CashAccount::factory()->create();
    $import = BankStatementImport::factory()->create(['cash_account_id' => $cashAccount->id]);

    return compact('cashAccount', 'import');
}

it('creates a high confidence 1:1 match when amount, date and reference all coincide', function () {
    $data = proposeMatchesFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'line_date' => '2026-07-05',
        'amount' => 100000,
        'reference' => 'REF-100',
    ]);

    $payment = Payment::factory()->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 100000,
        'reference' => 'REF-100',
        'is_bancarized' => true,
    ]);

    app(ProposeBankStatementMatches::class)->handle($data['import']);

    $matches = $line->matches()->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->confidence)->toBe(BankStatementMatchConfidence::High)
        ->and($matches->first()->payments->pluck('id')->all())->toBe([$payment->id]);
});

it('creates a candidate 1:1 match when the reference does not match', function () {
    $data = proposeMatchesFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'line_date' => '2026-07-05',
        'amount' => 100000,
        'reference' => 'REF-100',
    ]);

    Payment::factory()->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 100000,
        'reference' => null,
    ]);

    app(ProposeBankStatementMatches::class)->handle($data['import']);

    $matches = $line->matches()->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->confidence)->toBe(BankStatementMatchConfidence::Candidate);
});

it('creates no match when there is no candidate near the amount or date', function () {
    $data = proposeMatchesFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'line_date' => '2026-07-05',
        'amount' => 50000,
        'reference' => 'REF-999',
    ]);

    Payment::factory()->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 999999,
        'reference' => 'REF-100',
    ]);

    app(ProposeBankStatementMatches::class)->handle($data['import']);

    expect($line->matches()->count())->toBe(0);
});

it('proposes a batch match when the pool is within the configured limit', function () {
    $data = proposeMatchesFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'line_date' => '2026-07-05',
        'amount' => 300000,
        'reference' => 'REF-BATCH',
    ]);

    $matchingPayments = Payment::factory()->count(3)->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 100000,
        'reference' => null,
    ]);

    Payment::factory()->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 250000,
        'reference' => null,
    ]);

    app(ProposeBankStatementMatches::class)->handle($data['import']);

    $batchMatch = $line->matches()->get()->first(fn ($match) => $match->payments->count() === 3);

    expect($batchMatch)->not->toBeNull()
        ->and($batchMatch->confidence)->toBe(BankStatementMatchConfidence::Candidate)
        ->and($batchMatch->payments->pluck('id')->sort()->values()->all())
        ->toBe($matchingPayments->pluck('id')->sort()->values()->all());
});

it('never suggests a batch match when the candidate pool exceeds the configured limit', function () {
    $data = proposeMatchesFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'line_date' => '2026-07-05',
        'amount' => 300000,
        'reference' => 'REF-BATCH',
    ]);

    Payment::factory()->count(15)->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 100000,
        'reference' => null,
    ]);

    app(ProposeBankStatementMatches::class)->handle($data['import']);

    $batchMatches = $line->matches()->get()->filter(fn ($match) => $match->payments->count() > 1);

    expect($batchMatches)->toHaveCount(0);
});

it('excludes already reconciled payments from proposed matches', function () {
    $data = proposeMatchesFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'line_date' => '2026-07-05',
        'amount' => 100000,
        'reference' => 'REF-100',
    ]);

    $reconciled = Payment::factory()->create([
        'cash_account_id' => $data['cashAccount']->id,
        'paid_on' => '2026-07-05',
        'amount' => 100000,
        'reference' => 'REF-100',
    ]);
    $reconciled->update(['is_reconciled' => true]);

    app(ProposeBankStatementMatches::class)->handle($data['import']);

    expect($line->matches()->count())->toBe(0);
});

it('exposes the bank_reconciliation matching config with default values', function () {
    expect(config('contpass.bank_reconciliation.match_window_days'))->toBe(3)
        ->and(config('contpass.bank_reconciliation.match_pool_limit'))->toBe(10);
});

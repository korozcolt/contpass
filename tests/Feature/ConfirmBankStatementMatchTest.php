<?php

use App\Enums\BankStatementLineStatus;
use App\Enums\BankStatementMatchConfidence;
use App\Enums\BankStatementMatchStatus;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\CashAccount;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\ConfirmBankStatementMatch;

function confirmMatchFixture(): array
{
    $cashAccount = CashAccount::factory()->create();
    $import = BankStatementImport::factory()->create(['cash_account_id' => $cashAccount->id]);
    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $import->id,
        'status' => BankStatementLineStatus::Pending,
    ]);

    return compact('cashAccount', 'import', 'line');
}

it('marks the single payment as reconciled and confirms the match on a 1:1 confirmation', function () {
    $data = confirmMatchFixture();
    $user = User::factory()->create();
    $payment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);

    $match = $data['line']->matches()->create([
        'confidence' => BankStatementMatchConfidence::High,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payment->id);

    app(ConfirmBankStatementMatch::class)->handle($match, $user->id);

    expect($payment->fresh()->is_reconciled)->toBeTrue()
        ->and($payment->fresh()->reconciled_at)->not->toBeNull();

    $match->refresh();
    expect($match->status)->toBe(BankStatementMatchStatus::Confirmed)
        ->and($match->confirmed_at)->not->toBeNull()
        ->and($match->confirmed_by)->toBe($user->id);
});

it('marks all payments as reconciled on a batch confirmation', function () {
    $data = confirmMatchFixture();
    $payments = Payment::factory()->count(3)->create(['cash_account_id' => $data['cashAccount']->id]);

    $match = $data['line']->matches()->create([
        'confidence' => BankStatementMatchConfidence::Candidate,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payments->pluck('id')->all());

    app(ConfirmBankStatementMatch::class)->handle($match);

    $payments->each(fn (Payment $payment) => expect($payment->fresh()->is_reconciled)->toBeTrue());
});

it('moves the line status from Pending to Matched on confirmation', function () {
    $data = confirmMatchFixture();
    $payment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);

    $match = $data['line']->matches()->create([
        'confidence' => BankStatementMatchConfidence::High,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payment->id);

    app(ConfirmBankStatementMatch::class)->handle($match);

    expect($data['line']->fresh()->status)->toBe(BankStatementLineStatus::Matched);
});

it('discards other proposed matches for the same line when one is confirmed', function () {
    $data = confirmMatchFixture();
    $payment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);
    $otherPayment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);

    $match = $data['line']->matches()->create([
        'confidence' => BankStatementMatchConfidence::High,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payment->id);

    $otherMatch = $data['line']->matches()->create([
        'confidence' => BankStatementMatchConfidence::Candidate,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $otherMatch->payments()->attach($otherPayment->id);

    app(ConfirmBankStatementMatch::class)->handle($match);

    expect($otherMatch->fresh()->status)->toBe(BankStatementMatchStatus::Discarded)
        ->and($data['line']->matches()->where('status', BankStatementMatchStatus::Proposed)->count())->toBe(0);
});

it('is atomic - no payment ends up reconciled if the operation fails midway', function () {
    $data = confirmMatchFixture();
    $paymentA = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);
    $paymentB = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);

    $match = $data['line']->matches()->create([
        'confidence' => BankStatementMatchConfidence::Candidate,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach([$paymentA->id, $paymentB->id]);

    Payment::updating(function (Payment $payment) use ($paymentB) {
        if ($payment->is($paymentB)) {
            throw new RuntimeException('Simulated failure for atomicity test');
        }
    });

    expect(fn () => app(ConfirmBankStatementMatch::class)->handle($match))
        ->toThrow(RuntimeException::class);

    expect($paymentA->fresh()->is_reconciled)->toBeFalse()
        ->and($paymentB->fresh()->is_reconciled)->toBeFalse()
        ->and($match->fresh()->status)->toBe(BankStatementMatchStatus::Proposed)
        ->and($data['line']->fresh()->status)->toBe(BankStatementLineStatus::Pending);
});

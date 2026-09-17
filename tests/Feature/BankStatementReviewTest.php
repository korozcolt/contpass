<?php

use App\Enums\BankStatementLineStatus;
use App\Enums\BankStatementMatchConfidence;
use App\Enums\BankStatementMatchStatus;
use App\Filament\Pages\BankStatementReview;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\CashAccount;
use App\Models\Payment;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * @return array{cashAccount: CashAccount, import: BankStatementImport}
 */
function bankStatementReviewFixture(): array
{
    $cashAccount = CashAccount::factory()->create();
    $import = BankStatementImport::factory()->create(['cash_account_id' => $cashAccount->id]);

    return compact('cashAccount', 'import');
}

/**
 * `mount()` reads the import id from the query string (D-09), so it must be
 * present on the underlying request before the component boots — setting the
 * `importId` property afterwards is too late for the first render pass.
 */
function testBankStatementReview(int $importId): Testable
{
    return Livewire::withQueryParams(['import' => $importId])
        ->test(BankStatementReview::class);
}

it('shows the pending line with its proposed match and the confirm cruce button', function () {
    $this->actingAs(User::factory()->create());
    $data = bankStatementReviewFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'status' => BankStatementLineStatus::Pending,
    ]);
    $payment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);
    $match = $line->matches()->create([
        'confidence' => BankStatementMatchConfidence::High,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payment->id);

    testBankStatementReview($data['import']->id)
        ->assertSuccessful()
        ->assertSee('Confirmar cruce');
});

it('confirms a proposed match, reconciles the payment, and removes the line from the pending table', function () {
    $this->actingAs(User::factory()->create());
    $data = bankStatementReviewFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'status' => BankStatementLineStatus::Pending,
    ]);
    $payment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);
    $match = $line->matches()->create([
        'confidence' => BankStatementMatchConfidence::High,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payment->id);

    $component = testBankStatementReview($data['import']->id);

    $component->callTableAction('confirm', $line, data: ['match_id' => $match->id]);

    expect($payment->fresh()->is_reconciled)->toBeTrue();

    $component->assertCanNotSeeTableRecords([$line]);
});

it('discards a proposed match without touching any payment', function () {
    $this->actingAs(User::factory()->create());
    $data = bankStatementReviewFixture();

    $line = BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'status' => BankStatementLineStatus::Pending,
    ]);
    $payment = Payment::factory()->create(['cash_account_id' => $data['cashAccount']->id]);
    $match = $line->matches()->create([
        'confidence' => BankStatementMatchConfidence::Candidate,
        'status' => BankStatementMatchStatus::Proposed,
    ]);
    $match->payments()->attach($payment->id);

    $component = testBankStatementReview($data['import']->id);

    $component->callTableAction('discard', $line, data: ['match_id' => $match->id]);

    expect($payment->fresh()->is_reconciled)->toBeFalse()
        ->and($match->fresh()->status)->toBe(BankStatementMatchStatus::Discarded);

    $component->assertCanSeeTableRecords([$line]);
});

it('shows the "Todo conciliado" empty state when no pending lines remain', function () {
    $this->actingAs(User::factory()->create());
    $data = bankStatementReviewFixture();

    BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'status' => BankStatementLineStatus::Matched,
    ]);

    testBankStatementReview($data['import']->id)
        ->assertSuccessful()
        ->assertSee('Todo conciliado');
});

it('shows the "Filas rechazadas" banner with the reject reason when the import has rejected lines', function () {
    $this->actingAs(User::factory()->create());
    $data = bankStatementReviewFixture();

    BankStatementLine::factory()->create([
        'bank_statement_import_id' => $data['import']->id,
        'status' => BankStatementLineStatus::Rejected,
        'reject_reason' => 'No se pudo interpretar la fecha [31/13/2026].',
    ]);

    testBankStatementReview($data['import']->id)
        ->assertSuccessful()
        ->assertSee('Filas rechazadas')
        ->assertSee('No se pudo interpretar la fecha [31/13/2026].');
});

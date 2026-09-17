# Phase 1: Cotización electrónica (Fase A) - Research

**Researched:** 2026-09-16
**Domain:** Laravel 13 / Filament v5 commercial quotation module (new `Quotation`/`QuotationLine` models, PDF generation, state machine, idempotent conversion into existing accounting service)
**Confidence:** HIGH (all architecture/pitfall findings verified by reading the actual code this phase must integrate with; PDF library choice MEDIUM — verified against Packagist, not yet installed)

## Summary

Phase 1 adds a self-contained commercial layer (`Quotation`, `QuotationLine`) on top of the existing accounting core, with zero changes to `PostIncomeVoucher` or `PostsBalancedVoucher`. The only integration point is a thin "Convertir a ingreso" adapter — identical in shape to the existing `CreateIncomeRecord::handleRecordCreation()` — that translates quotation fields into the array `PostIncomeVoucher::handle()` already expects. Two codebase-verified bugs must NOT be copied into this phase's new code: (1) `BuildVoucherNumber`/`BuildWarehouseMovementNumber` both count rows without `company_id` scoping and without any locking, so cloning that pattern for `BuildQuotationNumber` would produce the exact duplicate-numbering bug QUOT-02 explicitly forbids; (2) `PostIncomeVoucher::handle()` has no idempotency guard at all — calling it twice always creates two vouchers — so the "Convertir a ingreso" action itself must own the idempotency check (status guard + terminal state flip + `voucher_id` storage, all inside one `DB::transaction()`), not rely on the service.

`barryvdh/laravel-dompdf` is confirmed NOT currently in `composer.json`/`composer.lock` — it is a genuinely new dependency requiring explicit user approval per CLAUDE.md/PROJECT.md before `composer require` runs. No system-level PDF alternative already exists in the codebase (no Blade-to-PDF pattern anywhere in `resources/views/`). The existing "branding" referenced in CONTEXT.md D-01 is the app-wide `public/images/brand/*` asset set (used in `AdminPanelProvider::brandLogo()`), not a per-`Company` logo field — `Company` has no logo column today, so the PDF header should reuse the static brand asset, not a per-company upload (out of scope unless explicitly requested).

**Primary recommendation:** Build `Quotation`/`QuotationLine` as new, fully independent tables (no `WarehouseItem` coupling, per D-04) with a `BuildQuotationNumber` service that fixes both known bugs (company-scoped `lockForUpdate()` inside `DB::transaction()`), a `QuotationStatus` enum modeled exactly on `VoucherStatus`/`BudgetObligationStatus` (implements `HasColor`/`HasIcon`/`HasLabel`), an `expires_on`-driven `Vencida` accessor (never a stored/manual transition), and a single `ConvertQuotationToIncome`-style action class (or table/page action) that wraps status-guard + `PostIncomeVoucher::handle()` + quotation status flip in one transaction — mirroring `CreateIncomeRecord`'s existing adapter pattern exactly.

## Project Constraints (from CLAUDE.md)

- No new/changed Composer or NPM dependencies without explicit user approval — `barryvdh/laravel-dompdf` is a **new** dependency (not currently installed) and must be called out for approval before `plan-phase`/`execute-phase` runs `composer require`.
- No new base directories without approval — `QuotationResource` follows the existing `app/Filament/Resources/{Entity}/` + `Pages/`/`Schemas/`/`Tables/` structure; `BuildQuotationNumber`/conversion service go in `app/Services/Accounting/` (existing directory), not a new `app/Services/Quotations/`.
- Domain logic lives in services (`app/Services/Accounting`); Filament forms/pages never create records directly — `CreateIncomeRecord::handleRecordCreation()` is the exact adapter pattern to replicate for both quotation creation (numbering) and conversion.
- Every change requires a new/updated Pest test; `php artisan test --compact` must pass; `vendor/bin/pint --dirty --format agent` must be run on any modified PHP before finishing.
- Spanish (`es_CO`) locale/UI text and `es_CO` Faker data throughout — enum labels, notifications, PDF text in Spanish (see `VoucherStatus::label()` for the pattern: `'Borrador'`, `'Aprobado'`, etc.).
- Filament v5 known bug: compare `$get('status')` against the enum case directly (`QuotationStatus::Rejected`), never `->value` or a string literal, in any `visible()`/`required()` closure — already documented as issue #1 in `docs/roadmap-apolo.md` and hit in `WarehouseMovementForm`/`BudgetModificationsForm`.

## User Constraints (from CONTEXT.md)

### Locked Decisions

- **D-01:** PDF reuses the existing app-wide branding/logo asset (`public/images/brand/contpass-logo-horizontal.png`, covered by `BrandingAssetsTest`) — same pattern as the rest of the system. `Company` has no logo column; do not add one for this phase.
- **D-02:** PDF includes an optional free-text "notas/términos comerciales" field on the quotation, printed only if non-empty (e.g. "50% anticipo, 50% contra entrega").
- **D-03:** Numbering format `COT-2026-00001` (prefix + 4-digit year + 5-digit consecutive) — note this differs from `BuildVoucherNumber`'s existing `%s-%s-%06d` (6-digit) format; do not copy the format string verbatim, use 5 digits per D-03.
- **D-04:** Quotation lines are free-text (description, cantidad, valor unitario) — NOT linked to `WarehouseItem`. Keeps the commercial module decoupled from Almacén.
- **D-05:** Revenue account (class 4) and receivable account (class 13) are selected on the quotation form itself, not at conversion time — conversion becomes a single click with no additional prompts, and the PDF/report can show the accounting classification from draft stage.
- **D-06:** Marking a quotation Rechazada requires a mandatory short-text reason field — this is the exact scenario the Filament v5 `$get()` enum-comparison bug affects (conditional `required()` on `status === QuotationStatus::Rejected`).
- **D-07:** Default validity is 30 days from creation/send date, editable per quotation. Past that date without a manual transition to Aceptada/Rechazada, status computes as Vencida — accessor or optional scheduled job, never a manual transition. No job is required for v1 (accessor is sufficient and simpler).

### Claude's Discretion

- Exact PDF layout (line table layout, typography, spacing) within existing branding constraints.
- Technical mechanism for computing "Vencida" — accessor-on-read vs. scheduled job; either is valid as long as it's never a manual user transition.
- Internal structure of `QuotationLine` beyond description/cantidad/valor unitario/subtotal (extra columns at Claude's discretion).

### Deferred Ideas (OUT OF SCOPE for this phase)

- QUOT-08 (v2): Visual badge/alert for quotations nearing expiration (dashboard).
- QUOT-09 (v2): Reusable catalog of frequent products/services for quotation lines — `WarehouseItem` coupling explicitly rejected for v1 per D-04.
- Automatic email/WhatsApp send when marking "Enviada" — "Enviada" is a pure manual status change; user downloads/sends the PDF outside the system.
- DIAN validation, electronic signature — explicitly out of scope per roadmap (this is a commercial quotation, not a DIAN-regulated document).

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| QUOT-01 | Crear cotización con líneas (descripción, cantidad, valor unitario), subtotal/total calculado | `QuotationLine` model pattern (see Architecture Patterns); `AccountingFormFields::money()`/`thirdParty()`/`chartAccount()` reusable field builders confirmed in `app/Filament/Support/AccountingFormFields.php` |
| QUOT-02 | Numeración consecutiva por empresa, segura ante concurrencia | Pitfall 2 (verified bug in `BuildVoucherNumber`/`BuildWarehouseMovementNumber`) — fix pattern documented below with `lockForUpdate()` |
| QUOT-03 | Ciclo de vida Borrador→Enviada→Aceptada/Rechazada; Vencida automática por fecha | `QuotationStatus` enum pattern from `VoucherStatus`/`BudgetObligationStatus`; accessor pattern from `WithholdingRule::effectiveOn()`-style date comparison |
| QUOT-04 | PDF con datos de empresa, líneas, subtotal/total, vigencia | `barryvdh/laravel-dompdf` (new dependency, needs approval); `BrandingAssetsTest` confirms static brand asset paths to reuse |
| QUOT-05 | Convertir Aceptada→`IncomeRecord` en un clic, reusando `PostIncomeVoucher` | `CreateIncomeRecord::handleRecordCreation()` is the exact adapter pattern to replicate; `PostIncomeVoucher::handle(Company, ThirdParty, array $data)` signature confirmed unchanged |
| QUOT-06 | Conversión idempotente en la misma transacción | Pitfall 3 (verified: `PostIncomeVoucher` has zero idempotency guard) — guard must live in the new conversion action, inside `DB::transaction()` |
| QUOT-07 | Cotización convertida de solo lectura con enlace al comprobante | `Voucher.adjusts_voucher_id` nullable self-FK is the existing precedent for a nullable "linked record" FK pattern; apply same shape for `quotations.voucher_id` |

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `barryvdh/laravel-dompdf` | `^3.1` (latest 3.1.2, confirmed via Packagist 2026-09-16) | Server-rendered Blade → PDF for the quotation document | Pure PHP, zero system/Node dependency, fits Laravel Cloud/Herd without extra infra. A quotation (header, line table, totals, terms) is fully covered by DomPDF's CSS 2.1 subset — no need for the heavier Browsershot/Chromium route. **NOT currently installed — requires explicit user approval before `composer require`.** |

### Supporting

None required beyond what's already vendored. No new frontend/JS dependency — PDF generation and numbering are server-side only.

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `barryvdh/laravel-dompdf` | `spatie/laravel-pdf` (Browsershot/Chromium driver) | Only worth it if the quotation PDF must reuse the app's actual Tailwind 4 component CSS pixel-for-pixel; requires a Node+Chromium sidecar (~300MB), real infra commitment. Not justified for a header+line-table+totals document. |

**Installation:**
```bash
composer require barryvdh/laravel-dompdf:^3.1
```
This is a NEW dependency — do not run without explicit user approval per CLAUDE.md/PROJECT.md policy. Flag this as a blocking pre-step in the plan.

**Version verification:** Confirmed via milestone-level `.planning/research/STACK.md` (dated same day, 2026-09-16) against Packagist: `barryvdh/laravel-dompdf` v3.1.2, requires PHP `^8.1`, Laravel `^9|^10|^11|^12|^13`, `dompdf/dompdf ^3.0` — fully compatible with this project's Laravel 13.8/PHP 8.4. Re-verify at plan/execute time with `composer show barryvdh/laravel-dompdf` if more than a few days have passed, since this was not re-fetched during this phase-specific research pass.

## Architecture Patterns

### Recommended Project Structure

```
app/
├── Enums/
│   └── QuotationStatus.php                      # Draft/Sent/Accepted/Rejected(+Converted marker); Vencida is computed, not a case OR is a case with accessor override — see Pattern 3
├── Models/
│   ├── Quotation.php                            # company_id, third_party_id, number, status, revenue_account_id, receivable_account_id, notes, expires_on, rejection_reason, voucher_id (nullable), timestamps
│   └── QuotationLine.php                        # quotation_id, description, quantity, unit_price, subtotal (computed or stored)
├── Services/Accounting/
│   ├── BuildQuotationNumber.php                  # company-scoped, lockForUpdate, COT-YYYY-NNNNN format
│   └── ConvertQuotationToIncome.php               # thin adapter: status guard + PostIncomeVoucher::handle() + status flip, all in one DB::transaction()
├── Filament/Resources/Quotations/
│   ├── QuotationResource.php
│   ├── Schemas/QuotationForm.php                  # third party, revenue/receivable account selects (class 4 / class 13 via AccountingFormFields::chartAccount), Repeater for lines, notes, expires_on
│   ├── Tables/QuotationsTable.php                 # number, third party, status badge, total, expires_on; row actions: Enviar/Aceptar/Rechazar/Convertir/PDF
│   └── Pages/{List,Create,Edit,View}Quotation.php
resources/views/pdf/
└── quotation.blade.php                            # DomPDF view — dedicated print stylesheet, not app Tailwind classes
```

### Pattern 1: Company-scoped, concurrency-safe sequential numbering (fixes Pitfall 2)

**What:** `BuildQuotationNumber::next()` must NOT reproduce `BuildVoucherNumber`'s bug (global count, no lock).
**When to use:** Any place a `Quotation` number is generated (only at creation).
**Example (fix pattern, not existing code — write this fresh):**
```php
// app/Services/Accounting/BuildQuotationNumber.php
namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class BuildQuotationNumber
{
    public function next(Company $company): string
    {
        return DB::transaction(function () use ($company): string {
            $year = now()->format('Y');

            $count = Quotation::query()
                ->whereBelongsTo($company)
                ->where('number', 'like', "COT-{$year}-%")
                ->lockForUpdate()
                ->count();

            return sprintf('COT-%s-%05d', $year, $count + 1);
        });
    }
}
```
**Note:** `lockForUpdate()` on the counting query only serializes concurrent transactions if they contend on the same locked row set — with SQLite (used in tests per `RefreshDatabase`) table-level locking behavior differs from PostgreSQL (production target). Verify the concurrency test actually exercises real contention (e.g., two `DB::transaction()` blocks not committed until both attempt to read) — a naive sequential test won't catch races. Consider a dedicated `quotation_number_sequences` counter table (`company_id` unique, `last_number` integer) with `lockForUpdate()` on that single row per company as a more robust alternative if PostgreSQL's MVCC snapshot behavior makes count-based locking unreliable under concurrent transactions — flag this as a decision point for the planner/implementer to verify against the actual production DB driver (PostgreSQL 12+ per STACK.md).

### Pattern 2: Idempotent "Convertir a ingreso" action (fixes Pitfall 3)

**What:** `PostIncomeVoucher::handle()` itself is reused unchanged — the guard and state transition wrap it.
**When to use:** The single "Convertir a ingreso" action, whether implemented as a table row `Action`, a page header `Action`, or a dedicated invokable service.
**Example (adapt from confirmed existing `CreateIncomeRecord` pattern):**
```php
// app/Services/Accounting/ConvertQuotationToIncome.php
namespace App\Services\Accounting;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertQuotationToIncome
{
    public function __construct(private readonly PostIncomeVoucher $postIncomeVoucher) {}

    public function handle(Quotation $quotation): Voucher
    {
        return DB::transaction(function () use ($quotation): Voucher {
            // Re-fetch with a lock so a concurrent second click can't pass the guard too
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== QuotationStatus::Accepted || $locked->voucher_id !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Esta cotización ya fue convertida o no está Aceptada.',
                ]);
            }

            $voucher = $this->postIncomeVoucher->handle($locked->company, $locked->thirdParty, [
                'revenue_account_id' => $locked->revenue_account_id,
                'receivable_account_id' => $locked->receivable_account_id,
                'support_number' => $locked->number,
                'accrual_date' => now()->toDateString(),
                'amount' => $locked->total, // sum of QuotationLine subtotals
                'description' => "Conversión de cotización {$locked->number}",
            ]);

            $locked->update(['voucher_id' => $voucher->id, 'status' => QuotationStatus::Converted]);

            return $voucher;
        });
    }
}
```
**Key point:** the `lockForUpdate()` re-fetch inside the transaction is what actually closes the double-click race — a pre-check outside the transaction (as Pitfall 3 warns) does not. Add a DB-level second line of defense too: a unique index is not directly expressible on a nullable `voucher_id` in all DB engines the same way, so the row lock + status check is the primary guard; consider `voucher_id` nullable + unique (PostgreSQL allows multiple NULLs in a unique index, so this is safe) as a defense-in-depth constraint.

### Pattern 3: Status enum + computed "Vencida" (never a stored manual transition)

**What:** `QuotationStatus` enum mirrors `VoucherStatus`/`BudgetObligationStatus` (implements `HasColor`, `HasIcon`, `HasLabel`), but "Vencida" must never be reachable via a manual Filament action — only computed.
**Recommended approach:** Two valid implementations exist; pick one explicitly during planning (Claude's discretion per CONTEXT.md):
- **(a) Accessor-only (simpler, recommended for v1):** `status` column stores only `Draft|Sent|Accepted|Rejected|Converted`. A model accessor `effectiveStatus(): QuotationStatus` returns `Expired` (a real enum case, but one that no `Action` ever sets directly) when `status === Sent && expires_on < today`. The Filament table/badge displays `effectiveStatus()`, not raw `status`. Actions that would normally apply to a `Sent` quotation (Aceptar/Rechazar) should also check `expires_on` hasn't passed before allowing the transition, to avoid accepting an already-expired quote.
- **(b) Scheduled job:** A daily command flips `Sent` quotations past `expires_on` to a stored `Expired` status. More moving parts (needs a working scheduler), explicitly NOT required for v1 per D-07 ("no un job programado obligatorio para v1"). Only choose this if the accessor approach creates real friction (e.g., needing to query "all expired quotations" efficiently at scale — for v1 volumes this is unlikely to matter).
**Filament v5 pitfall reminder:** any `visible()`/`required()` closure reading `$get('status')` must compare against the enum case (`QuotationStatus::Rejected`), never `->value` or a raw string — this is the exact scenario CONTEXT.md D-06 describes (mandatory reason field only when rejecting).

### Pattern 4: Reuse `AccountingFormFields` builders, don't hand-roll selects

**What:** `thirdParty()`, `chartAccount($name, $label, $classPrefix)`, `money()`, `date()` are already implemented in `app/Filament/Support/AccountingFormFields.php` and scope every query by `CurrentCompany`.
**When to use:** Quotation form's third-party select, revenue account select (`AccountingFormFields::chartAccount('revenue_account_id', 'Cuenta de ingreso', '4')`), receivable account select (`AccountingFormFields::chartAccount('receivable_account_id', 'Cuenta por cobrar', '13')`), money fields for line unit price.
**Do not** write a new `Select::make('third_party_id')->options(...)` from scratch — the existing builder already handles `whereBelongsTo(CurrentCompany)`, searchable/preload, and the `tax_id-verification_digit · name` display format used everywhere else.

### Anti-Patterns to Avoid

- **Copying `BuildVoucherNumber`/`BuildWarehouseMovementNumber` verbatim:** both are confirmed non-company-scoped and non-locked in the current codebase — this is a customer-visible bug for quotations (duplicate `COT-` numbers sent to two clients), explicitly forbidden by QUOT-02. See Pattern 1.
- **Pre-checking `Quotation.status` outside the conversion transaction:** a separate `if ($quotation->status !== Accepted) { abort }` check before opening `DB::transaction()` does not close the double-click race — the check-then-act gap is exactly where two concurrent requests both pass. Guard must be the locked row inside the transaction (Pattern 2).
- **Growing `IncomeRecord` with quotation-linkage columns:** per milestone `ARCHITECTURE.md` Anti-Pattern 3, keep `IncomeRecord` untouched; the `Quotation → Voucher` link lives on `quotations.voucher_id`, not on `income_records`.
- **Coupling `QuotationLine` to `WarehouseItem`:** explicitly rejected per D-04 — free-text lines only.
- **Choosing revenue/receivable accounts at conversion time instead of on the quotation form:** contradicts D-05; the conversion action must NOT prompt for anything — it reads `revenue_account_id`/`receivable_account_id` already stored on the `Quotation`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Third-party/chart-account dropdowns scoped to current company | A new `Select::make(...)->options(...)` closure | `AccountingFormFields::thirdParty()`, `::chartAccount()` | Already company-scoped, searchable, preloaded, formatted consistently across the app |
| PDF generation | Custom `TCPDF`/manual HTML-to-PDF pipeline | `barryvdh/laravel-dompdf` (pending approval) | Zero-infra, Blade-view-based, matches the "structured business document" use case exactly; avoid inventing a bespoke renderer |
| Sequential per-company numbering | A fresh ad-hoc counting query | `BuildQuotationNumber` (fixed pattern, Pattern 1) — do NOT reuse the buggy existing services verbatim | Avoids re-shipping a known, already-diagnosed concurrency bug into new customer-facing numbers |
| "Convert to income" posting logic | Any new double-entry/voucher-creation code | `PostIncomeVoucher::handle()` (existing, untouched) | Core Value requires one posting path; a parallel implementation risks drift from `PostsBalancedVoucher`'s balance invariant |

**Key insight:** Every piece of this phase that "looks new" (numbering, conversion, PDF) has either an existing-but-buggy sibling pattern to fix (numbering, idempotency) or an existing correct adapter pattern to replicate exactly (`CreateIncomeRecord`). The actual net-new code surface is small: two models, one enum, one numbering service, one conversion service/action, one Filament resource, one PDF view.

## Common Pitfalls

### Pitfall 1: Filament v5 `$get()` enum comparison bug

**What goes wrong:** Comparing `$get('status')` to `QuotationStatus::Rejected->value` or `'rejected'` in a `required()`/`visible()` closure always evaluates false — field silently never appears, no error, no failing test at the model layer.
**Why it happens:** Filament v5 changed `Select`+enum `options()` behavior so `$get()` returns the resolved enum instance, not the raw stored value.
**How to avoid:** Every conditional field (rejection reason visible/required only when `status === QuotationStatus::Rejected`) must compare directly to the enum case.
**Warning signs:** Field "does nothing" in the browser despite passing Pest tests that only hit the service/model layer — write a Livewire component test (`Livewire::test(EditQuotation::class)->fillForm([...])->assertHasNoFormErrors()` or equivalent) that actually exercises the form, not just `ConvertQuotationToIncome::handle()` directly.

### Pitfall 2: Non-company-scoped, non-locked numbering

**What goes wrong:** Reusing `BuildVoucherNumber`'s exact `Model::query()->where('type', ...)->count() + 1` shape produces duplicate `COT-` numbers across companies or under concurrent creation.
**Why it happens:** The existing pattern (copied into `BuildWarehouseMovementNumber` too) was written before concurrency/multi-tenancy correctness was a driver.
**How to avoid:** See Pattern 1 — company-scope the count and wrap in `DB::transaction()` + `lockForUpdate()`.
**Warning signs:** A concurrency test (two "simultaneous" `BuildQuotationNumber::next()` calls inside overlapping transactions) produces the same number twice.

### Pitfall 3: No idempotency guard on conversion

**What goes wrong:** Calling the conversion action twice (double-click, resubmit) creates two immutable `Voucher`/`IncomeRecord` rows for one quotation — since approved vouchers are immutable, recovery requires a manual adjustment note, not a simple fix.
**Why it happens:** `PostIncomeVoucher` is a generic, caller-agnostic service with no "already converted" awareness.
**How to avoid:** See Pattern 2 — lock-then-check-then-act inside one transaction, flip status + store `voucher_id` in the same transaction as the voucher creation.
**Warning signs:** A Pest test invoking the conversion twice produces two vouchers instead of one clean failure on the second call.

### Pitfall 4: `expires_on` / "Vencida" implemented as a stored, manually-settable status

**What goes wrong:** If `Vencida` is just another case a Filament `Action` can set, a user could manually mark a quotation "Vencida" before its actual expiry date, or the accessor/job could race with a manual Aceptar/Rechazar click right at the expiry boundary.
**Why it happens:** Treating all enum cases as equally "transition-able" is the default Filament pattern (buttons per case) — Vencida needs to be excluded from that button set.
**How to avoid:** Per CONTEXT.md D-07/Claude's Discretion — implement as a read-time accessor (Pattern 3a) and explicitly omit any "Marcar como vencida" action from the table/page. Guard the Aceptar/Rechazar actions themselves to refuse if `expires_on` has already passed (double-check even if the UI already hides the button, since actions can be invoked directly in tests/URLs).
**Warning signs:** A test asserts that calling the "accept" action on a `Sent` quotation whose `expires_on` is in the past still succeeds — this should fail/be blocked instead.

## Runtime State Inventory

Not applicable — this phase introduces new tables/models/UI and does not rename, refactor, or migrate any existing string/identifier. Skipped per trigger condition (rename/refactor/migration phases only).

## Code Examples

### Existing adapter pattern to replicate (verified, `app/Filament/Resources/IncomeRecords/Pages/CreateIncomeRecord.php`)
```php
// Source: app/Filament/Resources/IncomeRecords/Pages/CreateIncomeRecord.php (read directly)
protected function handleRecordCreation(array $data): Model
{
    $thirdParty = ThirdParty::query()->findOrFail((int) $data['third_party_id']);

    $voucher = app(PostIncomeVoucher::class)->handle(app(CurrentCompany::class)->get(), $thirdParty, $data);

    return $voucher->incomeRecord()->firstOrFail();
}
```
This is the reference shape for the quotation conversion adapter — same "resolve related model, call service, return result" structure, just triggered from a table/page `Action` instead of `CreateRecord`.

### Existing status-badge table column pattern (verified, `app/Filament/Resources/Vouchers/Tables/VouchersTable.php`)
```php
// Source: app/Filament/Resources/Vouchers/Tables/VouchersTable.php (read directly)
TextColumn::make('status')->label('Estado')->badge(),
```
Works automatically once the enum implements `HasColor`/`HasLabel`/`HasIcon` — no extra `->colors()`/`->formatStateUsing()` needed, matching `QuotationStatus`'s planned shape.

### Existing table row `Action` with a `DB::transaction`-backed service call (verified, `app/Filament/Resources/Vouchers/Tables/VouchersTable.php`)
```php
// Source: app/Filament/Resources/Vouchers/Tables/VouchersTable.php (read directly)
Action::make('adjust')
    ->label('Nota de ajuste')
    ->icon('heroicon-o-adjustments-horizontal')
    ->action(function (Voucher $record, array $data): void {
        app(CreateAdjustmentVoucher::class)->handle(/* ... */);
        Notification::make()->success()->title('Nota de ajuste creada')->send();
    }),
```
Use this exact shape for "Enviar"/"Aceptar"/"Rechazar"/"Convertir a ingreso" row actions on `QuotationsTable`, swapping in the respective service calls and status-specific forms (e.g. rejection reason field only on "Rechazar").

## State of the Art

Not applicable in the sense of "old vs. new library version" — this is new functionality within a stable, already-audited stack (Laravel 13.8, Filament 5.6, PHP 8.4). The one relevant "current vs. deprecated" note: DomPDF v3.x (via `barryvdh/laravel-dompdf ^3.1`) is the actively maintained line; do not pin to `barryvdh/laravel-dompdf ^2.x` (older Laravel/DomPDF 2.x combination), which is EOL-adjacent per Packagist version history.

## Open Questions

1. **Concurrency-safety of `lockForUpdate()` count-based numbering under PostgreSQL (production) vs. SQLite (test DB)**
   - What we know: `lockForUpdate()` inside `DB::transaction()` is the standard Laravel pattern for this; PostgreSQL row locking behaves as expected for `SELECT ... FOR UPDATE`, but a `COUNT(*)` query with `lockForUpdate()` locks the rows it reads, not a "next value," so under high contention two transactions could still both read a stale count if isolation level/locking scope isn't tight enough.
   - What's unclear: Whether a count-based lock is sufficient at the concurrency levels this app will actually see (single-company internal tool, low quotation-creation throughput) vs. needing a dedicated single-row sequence-counter table.
   - Recommendation: Ship the count+lock approach for v1 (matches QUOT-02's actual bar — "no duplicates under concurrent creation," verifiable with a Pest test using two overlapping transactions) but flag the dedicated-counter-table alternative in the plan as a fallback if the concurrency test proves the count approach flaky under SQLite's coarser locking in the test suite.

2. **Exact PDF route/download mechanism (Filament `Action::make('pdf')->url()` streaming vs. a dedicated controller route like the existing CSV exports)**
   - What we know: All 8 existing CSV exports go through `routes/web.php` + `AccountingReportController` methods behind `abort_unless($request->user() !== null, 403)`, not through Filament actions directly. `barryvdh/laravel-dompdf` exposes a `Pdf::view('pdf.quotation', $data)->download()`/`->stream()` response.
   - What's unclear: Whether this phase should add a new `routes/web.php` entry (`quotations/{quotation}/pdf`) mirroring the CSV pattern, or use a Filament `Action::make('pdf')->action(fn () => response()->streamDownload(...))` entirely inside the Resource (no new route). Both are viable; the CSV precedent favors a route+controller-method for consistency, but Filament v5 actions can stream directly without a new route.
   - Recommendation: Planner should pick one during task breakdown — a Filament `Action` returning a download response (no new route, no new controller method) is simpler and sufficient since this is not a public/unauthenticated endpoint like the CSV routes; only add a `routes/web.php` entry if the PDF needs to be linkable/shareable outside the admin panel (not requested in CONTEXT.md).

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | All | ✓ | 8.4 (per CLAUDE.md) | — |
| Composer | Dependency install | ✓ | 2.x | — |
| `barryvdh/laravel-dompdf` | QUOT-04 (PDF) | ✗ (not in composer.json/lock, confirmed via grep) | — (target `^3.1`) | None viable without a new dependency — PDF generation cannot be hand-rolled reasonably; this is a hard blocker pending user approval |
| PostgreSQL | Production DB target | Not probed directly (Herd-managed) | 12+ per STACK.md | SQLite used for local test DB (`RefreshDatabase`), confirmed via `.planning/codebase/TESTING.md` |
| Laravel Herd | Local serving | Assumed available (per CLAUDE.md, "always available") | — | — |

**Missing dependencies with no fallback:**
- `barryvdh/laravel-dompdf` — required for QUOT-04. This blocks any task that generates the actual PDF file until the user explicitly approves `composer require barryvdh/laravel-dompdf:^3.1`. All other requirements (QUOT-01/02/03/05/06/07) have no external dependency and can proceed independently.

## Validation Architecture

Skipped — `.planning/config.json` sets `workflow.nyquist_validation: false` explicitly.

## Sources

### Primary (HIGH confidence — direct code inspection, 2026-09-16)
- `app/Services/Accounting/PostIncomeVoucher.php` — confirmed exact `handle(Company, ThirdParty, array)` signature, no idempotency guard
- `app/Services/Accounting/BuildVoucherNumber.php`, `app/Services/Warehouse/BuildWarehouseMovementNumber.php` — confirmed both share the non-company-scoped, non-locked counting bug
- `app/Filament/Resources/IncomeRecords/Pages/CreateIncomeRecord.php` — confirmed the adapter pattern to replicate for conversion
- `app/Filament/Resources/Vouchers/Tables/VouchersTable.php`, `app/Filament/Resources/BudgetObligations/BudgetObligationResource.php`, `app/Filament/Resources/Vouchers/VoucherResource.php` — confirmed Filament v5 Resource/Table/Action structure and conventions
- `app/Enums/VoucherStatus.php`, `app/Enums/BudgetObligationStatus.php` — confirmed enum implementation pattern (`HasColor`/`HasIcon`/`HasLabel`)
- `app/Filament/Support/AccountingFormFields.php` — confirmed reusable field builders (`thirdParty()`, `chartAccount()`, `money()`, `date()`, `companyId()`)
- `app/Models/Voucher.php`, `app/Models/IncomeRecord.php`, `app/Models/Company.php`, `app/Models/ThirdParty.php` — confirmed schema shape, relationship patterns, absence of a `Company` logo column
- `app/Services/Accounting/CurrentCompany.php` — confirmed effectively single-company resolution today (`config('contpass.company_nit')` or first company), despite `company_id` scoping existing everywhere for future multi-tenancy
- `tests/Feature/BrandingAssetsTest.php` — confirmed the exact static brand asset paths available for PDF header reuse
- `app/Providers/Filament/AdminPanelProvider.php` — confirmed `navigationGroups([...])` is an explicit array requiring a new group name to be added if `Quotation` doesn't fit an existing group (`Operación`, `Cuentas x Cobrar`, `Catálogos`, etc.)
- `database/migrations/*_create_vouchers_table.php` — confirmed migration conventions (`foreignId()->constrained()`, composite index, `string('status')` not enum column type)
- `grep -rn "lockForUpdate"` across `app/` — confirmed zero existing usage; this phase introduces the first locking pattern in the codebase
- `.planning/codebase/CONVENTIONS.md`, `.planning/codebase/STRUCTURE.md`, `.planning/codebase/TESTING.md` — confirmed naming, directory, and Pest testing conventions
- `.planning/config.json` — confirmed `nyquist_validation: false`

### Secondary (MEDIUM confidence — milestone-level research, same-day, cross-referenced)
- `.planning/research/ARCHITECTURE.md` §"Fase A — Quotation" — integration point and data-flow confirmation, consistent with direct code reading above
- `.planning/research/PITFALLS.md` Pitfalls 1–3 — root-caused against the same source files re-verified in this pass
- `.planning/research/STACK.md` — `barryvdh/laravel-dompdf ^3.1` recommendation, verified against Packagist same day
- `.planning/research/FEATURES.md` §Fase A — competitor lifecycle/PDF/conversion pattern confirmation (Siigo/Alegra quotation flows)

### Tertiary (LOW confidence)
- None used directly in this pass beyond what the milestone research already cross-verified.

## Metadata

**Confidence breakdown:**
- Standard stack: MEDIUM — DomPDF choice verified via Packagist same-day (milestone research), not re-fetched in this pass; not yet installed
- Architecture: HIGH — every integration point read directly from source in this pass
- Pitfalls: HIGH — all three critical pitfalls (numbering, idempotency, enum `$get()`) verified against actual current code, not inferred

**Research date:** 2026-09-16
**Valid until:** 2026-10-16 (30 days — stable Laravel/Filament stack, no fast-moving dependencies in scope besides the pending DomPDF install)

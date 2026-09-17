---
phase: 03-conciliaci-n-bancaria-csv-fase-b
plan: 04
subsystem: ui
tags: [filament, livewire, forms, tables, bank-reconciliation, fileupload]

# Dependency graph
requires:
  - phase: 03-conciliaci-n-bancaria-csv-fase-b (Plan 02)
    provides: ImportBankStatement service (CSV parsing, encoding/delimiter detection, D-03/D-05 rejection)
  - phase: 03-conciliaci-n-bancaria-csv-fase-b (Plan 03)
    provides: ProposeBankStatementMatches / ConfirmBankStatementMatch services (matching engine)
provides:
  - UploadBankStatement Filament page (CashAccount + BankProfile + FileUpload form, invokes ImportBankStatement + ProposeBankStatementMatches)
  - BankStatementReview Filament page (per-import pending-lines table, confirm/discard match-picker actions, rejected-rows banner, empty state)
  - The only UI entry point into Fase B — closes BANKREC-01 and BANKREC-05 end to end
affects: [bank-reconciliation, treasury-ui]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Page-level ValidationException from a domain service must be re-thrown with the form's statePath-prefixed key (e.g. 'data.file') before it reaches Livewire's exception handler — Livewire's SupportValidation::exception() sets the error bag using the raw keys from the exception's validator, without prefixing by schema statePath. Confirmed existing precedent: App\\Filament\\Resources\\ThirdParties\\Pages\\CreateThirdParty already does this ('data.verification_digit')."
    - "Filament v5 FileUpload testing via fillForm() requires the fake UploadedFile wrapped in an array (['file' => [UploadedFile::fake()->createWithContent(...)]]) even for a non-multiple() field — Livewire's Testable::setProperty() only triggers the array/uuid-keyed upload path (matching FileUploadStateCast's internal expectation) when given an array; a bare UploadedFile takes the single-file path and produces a raw TemporaryUploadedFile that fails BaseFileUpload's internal `array $value` validation closure."
    - "Table::recordActions() only accepts a static array of Action|ActionGroup (no per-record closure returning a variable-length array) — a variable number of named actions per record (one Confirm/Discard pair per proposed match) is not supported. Implemented instead as two static actions ('confirm'/'discard') whose ->schema() closure (which IS per-record-aware) renders a match-picker Select, preserving individual per-suggestion control (BANKREC-05) within the real API."
    - "Livewire::withQueryParams(['import' => $id])->test(Page::class) is required (not ->set('importId', ...) after the fact) when a page's mount() reads request()->query() — mount() runs during the first render, before any subsequent ->set() call, so query-string-dependent state must be seeded before Testable::create()."

key-files:
  created:
    - app/Filament/Pages/UploadBankStatement.php
    - app/Filament/Pages/BankStatementReview.php
    - resources/views/filament/pages/upload-bank-statement.blade.php
    - resources/views/filament/pages/bank-statement-review.blade.php
    - tests/Feature/BankStatementUploadTest.php
    - tests/Feature/BankStatementReviewTest.php
  modified: []

key-decisions:
  - "UploadBankStatement::import() catches ImportBankStatement's ValidationException (bare 'file' key) and re-throws with 'data.file' so assertHasFormErrors(['file']) resolves correctly against the page's statePath('data') — the plan's assumption that the raw key propagates automatically does not hold for page-level (non-modal) Livewire actions."
  - "BankStatementReview's Confirmar cruce / Descartar sugerencia are two static row actions with a match-picker Select (defaulting to the highest-confidence proposed match), not one dynamically-named action pair per BankStatementMatch — Filament's recordActions() API does not support a variable-count per-record action list."
  - "FileUpload uses acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel']) instead of the plan's ->extensions(['csv', 'txt']) — no extensions() method exists on Filament v5.6's FileUpload/BaseFileUpload; acceptedFileTypes() (MIME-based) is the real API."
  - "Defensive enum handling for the Select('bank') field state (\$data['bank'] instanceof BankProfile ? \$data['bank'] : BankProfile::from(\$data['bank'])) per the already-documented Filament v5 enum-comparison bug (issue #1 / STATE.md Phase 02 P02 decision) — Select::options(EnumClass::class) can yield the enum case instance directly in \$data, not the raw backing value."

requirements-completed: [BANKREC-01, BANKREC-05]

# Metrics
duration: 55min
completed: 2026-09-17
---

# Phase 03 Plan 04: Bank Reconciliation Upload & Review UI Summary

**Two Filament pages (UploadBankStatement, BankStatementReview) wiring the CSV import and matching-engine services from Plans 02/03 into a full upload → review → confirm/discard user flow, with a Filament-API-compliant match-picker replacing the plan's unsupported per-match dynamic action list.**

## Performance

- **Duration:** ~55 min (including worktree re-sync from `main`, full environment bootstrap — composer/npm install, .env, Postgres role — and Filament v5 internals investigation)
- **Completed:** 2026-09-17T16:36:29Z
- **Tasks:** 2
- **Files modified:** 6 (all new)

## Accomplishments
- `UploadBankStatement` page: CashAccount + BankProfile + FileUpload form that calls `ImportBankStatement::handle()` then `ProposeBankStatementMatches::handle()`, redirecting to the specific import's review page on success; shows the exact D-03/D-05 error copy from 03-UI-SPEC.md on failure without creating any record
- `BankStatementReview` page: dedicated per-import page (not in global nav, reached via redirect or the "Imports recientes" list) showing pending lines with a candidate-count badge, a rejected-rows banner with each row's reject reason, and a "Todo conciliado" empty state
- Confirmar cruce / Descartar sugerencia act on an explicitly chosen `BankStatementMatch` (never automatic), calling `ConfirmBankStatementMatch`/updating match status directly — BANKREC-05 explicit-confirmation guarantee holds
- 8 new Pest/Livewire tests, all green; full suite 226/226 passing (up from 218 baseline)

## Task Commits

Each task was committed atomically:

1. **Task 1: UploadBankStatement — formulario CashAccount+BankProfile+FileUpload** - `bdf39d4` (feat)
2. **Task 2: BankStatementReview — tabla de líneas pendientes, banner de rechazadas, empty state** - `39d2682` (feat)

**Plan metadata:** pending (this commit)

## Files Created/Modified
- `app/Filament/Pages/UploadBankStatement.php` - Upload form page; invokes ImportBankStatement + ProposeBankStatementMatches, redirects to BankStatementReview, lists 10 most recent imports with links
- `app/Filament/Pages/BankStatementReview.php` - Per-import review page; pending-lines table (Eloquent `->query()`), match-picker confirm/discard actions, rejected-rows banner, empty state
- `resources/views/filament/pages/upload-bank-statement.blade.php` - Renders both `{{ $this->form }}` and `{{ $this->content }}` (recent imports list)
- `resources/views/filament/pages/bank-statement-review.blade.php` - Renders `{{ $this->content }}` (banner + embedded table)
- `tests/Feature/BankStatementUploadTest.php` - 3 tests: valid upload, D-03 exact error copy, D-05 overlap error
- `tests/Feature/BankStatementReviewTest.php` - 5 tests: visible confirm button, confirm reconciles + removes line, discard leaves payment untouched, empty state, rejected banner

## Decisions Made
See `key-decisions` in frontmatter. In short: (1) service `ValidationException` keys must be re-prefixed with the form's statePath before re-throwing at the page boundary; (2) FileUpload test fixtures must wrap the fake file in an array even for single-file mode; (3) `recordActions()` cannot hold a variable number of per-record actions, so Confirm/Discard are two static actions with a match-picker `Select` instead of one dynamically-named pair per suggestion; (4) `acceptedFileTypes()` replaces the plan's nonexistent `extensions()` call; (5) defensive enum-instance handling for the `bank` Select field per the already-known Filament v5 issue.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] ValidationException from ImportBankStatement does not auto-attach to the form field**
- **Found during:** Task 1 (UploadBankStatement)
- **Issue:** The plan instructed to let `ImportBankStatement`'s `ValidationException` (keyed `'file'`) propagate uncaught, assuming Filament auto-translates it into a form error on the `file` field. Tracing Livewire's `SupportValidation::exception()` hook shows it sets the error bag using the exception's raw keys with no statePath prefixing, so `assertHasFormErrors(['file'])` (which checks `'data.file'`) would never match a bare `'file'` key.
- **Fix:** `UploadBankStatement::import()` catches the `ValidationException` and re-throws it with the key re-prefixed to `'data.file'`, matching the existing project convention in `CreateThirdParty::validateTaxId()`.
- **Files modified:** app/Filament/Pages/UploadBankStatement.php
- **Verification:** `BankStatementUploadTest` D-03/D-05 tests assert the exact/partial error message against `'file'` via `assertHasFormErrors()`/`$component->errors()->first('data.file')` — both pass.
- **Committed in:** bdf39d4

**2. [Rule 3 - Blocking] FileUpload has no extensions() method in installed Filament v5.6**
- **Found during:** Task 1 (UploadBankStatement)
- **Issue:** The plan's `->extensions(['csv', 'txt'])` call does not exist on `Filament\Forms\Components\BaseFileUpload` in this version.
- **Fix:** Used `->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])`, the real MIME-based validation API.
- **Files modified:** app/Filament/Pages/UploadBankStatement.php
- **Verification:** Upload test with a CSV fixture passes validation and imports successfully.
- **Committed in:** bdf39d4

**3. [Rule 1 - Bug] Upload page blade only rendered {{ $this->form }}, hiding the "Imports recientes" list**
- **Found during:** Task 1 (UploadBankStatement)
- **Issue:** The plan built a recent-imports list via `content(Schema $schema)` but specified a blade view rendering only `{{ $this->form }}`, which would never display that list.
- **Fix:** Blade renders both `{{ $this->form }}` and `{{ $this->content }}`.
- **Files modified:** resources/views/filament/pages/upload-bank-statement.blade.php
- **Committed in:** bdf39d4

**4. [Rule 3 - Blocking] Table::recordActions() cannot hold a variable-length per-record action list**
- **Found during:** Task 2 (BankStatementReview)
- **Issue:** The plan's `ActionGroup::make(fn (BankStatementLine $record): array => ...)` fails at runtime — `ActionGroup::make()` requires `array $actions`, not a `Closure`; Filament's `recordActions()` API only supports a static list of actions, not one dynamically sized per record (needed here since a line can have 0..N proposed matches, each wanting its own Confirm/Discard pair).
- **Fix:** Replaced with two static actions (`confirm`, `discard`) whose `->schema()` (which does support a per-record closure) renders a `Select` of that record's proposed matches, defaulting to the highest-confidence one. The chosen match's id is submitted as form data and used by the action handler. `ConfirmBankStatementMatch`'s existing behavior (discarding sibling proposals on confirmation) means this fully preserves BANKREC-05's individual-confirmation guarantee without needing a control per suggestion in the table itself.
- **Files modified:** app/Filament/Pages/BankStatementReview.php
- **Verification:** `BankStatementReviewTest` confirm/discard tests pass, targeting a specific `match_id` via the action's form data.
- **Committed in:** 39d2682

---

**Total deviations:** 4 auto-fixed (2 blocking-API-mismatch, 1 bug/validation-key, 1 bug/missing-render)
**Impact on plan:** All four were necessary for the plan to function against the actually-installed Filament v5.6 API and Livewire's real validation-exception behavior. No scope creep — the UI/copy contract from 03-UI-SPEC.md and the two services' contracts from Plans 02/03 were used exactly as specified.

## Issues Encountered
- Worktree was not fast-forwarded from `main` at spawn time (missing `.planning/`, `vendor/`, `.env`, `node_modules/`, `public/build/` entirely) — same recurring pattern documented for every prior plan in this phase. Fixed with `git merge main --ff-only` (verified `git merge-base --is-ancestor HEAD main` first) followed by full bootstrap (`composer install`, `.env` + `APP_KEY`, `npm install && npm run build`). The shared Postgres container's `contpass` role/database and all migrations (through 03-01/03-02/03-03) were already provisioned from earlier plans in this phase and needed no changes beyond setting `DB_PASSWORD=contpass` locally.
- Confirmed via Laravel Boost MCP tools were unavailable in this session (not exposed as invokable tools despite being listed in the environment); relied on direct `Read`/`Bash` inspection of the installed `vendor/filament` and `vendor/livewire` source to resolve the Filament v5 API questions documented above.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

BANKREC-01 through BANKREC-06 are now complete end to end (schema/model in Plan 01, CSV import in Plan 02, matching engine in Plan 03, UI in this plan). A user can upload a bank statement CSV from `/admin`, see D-03/D-05 errors with the exact required copy, and confirm or discard each proposed match individually with no automatic confirmation. No blockers for closing Phase 03.

## Self-Check: PASSED

- FOUND: app/Filament/Pages/UploadBankStatement.php
- FOUND: app/Filament/Pages/BankStatementReview.php
- FOUND: resources/views/filament/pages/upload-bank-statement.blade.php
- FOUND: resources/views/filament/pages/bank-statement-review.blade.php
- FOUND: tests/Feature/BankStatementUploadTest.php
- FOUND: tests/Feature/BankStatementReviewTest.php
- FOUND commit: bdf39d4
- FOUND commit: 39d2682

---
phase: 03-conciliaci-n-bancaria-csv-fase-b
plan: 02
subsystem: accounting
tags: [csv-import, league-csv, encoding, pest, bank-reconciliation]

# Dependency graph
requires:
  - phase: 03-conciliaci-n-bancaria-csv-fase-b
    plan: "01"
    provides: BankProfile enum (columnAliases/dateFormats/requiredFields), BankStatementImport/BankStatementLine models with fillable+casts
provides:
  - "app/Services/Accounting/ImportBankStatement.php — handle(Company, CashAccount, BankProfile, string $path): BankStatementImport, único punto de entrada de datos de Fase B"
affects: ["03-03", "03-04"]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Encoding/delimiter detection via League\\Csv\\CharsetConverter stream filter + Info::getDelimiterStats (no new dependency, already vendored via filament/actions), replicating Filament\\Actions\\ImportAction's internal mechanism"
    - "Two-tier rejection: whole-file rejection (headers missing, period overlap) happens BEFORE any DB write; per-row rejection (bad date/amount) happens inside the same transaction as the import, never aborting the rest of the file"
    - "CarbonImmutable::hasFormat() before createFromFormat() to reject invalid dates strictly (no lenient rollover), following ArchiveMasterPreviewImporter::parseDate() precedent"

key-files:
  created:
    - app/Services/Accounting/ImportBankStatement.php
  modified:
    - tests/Feature/ImportBankStatementTest.php

key-decisions:
  - "Task 1 (whole-file validations) and Task 2 (row-level line creation) were implemented together in a single GREEN commit instead of two — the plan's TDD RED/GREEN split by task was followed at the test level (all 10 behaviors written and committed RED first), but the service code for both tasks was written as one cohesive class in one GREEN pass since splitting it into an intermediate half-working state added no verification value and doubled edit churn on the same file"
  - "Test CSV fixture builder must use fputcsv() (not manual string implode), because an unquoted amount value containing the delimiter character itself (e.g. '150.000,50' with comma delimiter) gets silently split into extra CSV fields — caught by the amount-normalization test failing with 150.0 instead of 150000.50, fixed before GREEN"

requirements-completed: [BANKREC-02, BANKREC-03, BANKREC-06]
requirements-partial: [BANKREC-01]

# Metrics
duration: ~55min
completed: 2026-09-17
---

# Phase 3 Plan 2: Bank Statement CSV Importer Summary

**`ImportBankStatement::handle()` parses a bank CSV (auto-detecting UTF-8/Windows-1252 encoding and comma/semicolon delimiter via `league/csv`), validates headers and per-CashAccount period overlap before writing anything, then creates `BankStatementLine` rows atomically — rejecting only the offending row (never the whole file) when a single row's date or amount can't be parsed.**

## Performance

- **Duration:** ~55 min (majority spent re-bootstrapping a stale/empty worktree — see Deviations)
- **Started:** 2026-09-17T~16:05Z (approx, worktree bootstrap)
- **Completed:** 2026-09-17T~17:00Z
- **Tasks:** 2 (implemented as 1 RED commit covering all 10 test behaviors + 1 GREEN commit covering both tasks' service logic)
- **Files modified:** 2 (1 created, 1 test file created then fixed)

## Accomplishments
- `ImportBankStatement` service: encoding detection (`mb_check_encoding` UTF-8-first, per Pitfall 2), delimiter auto-detection (`League\Csv\Info::getDelimiterStats`), header-alias resolution against `BankProfile::columnAliases()`
- Whole-file rejection (no records created) for unrecognizable headers (D-03) and for CashAccount-scoped period overlap (D-04/D-05), both validated strictly before opening any transaction
- Per-row rejection (BANKREC-06): unparseable date or non-numeric amount creates a `BankStatementLine` with `status=Rejected` + explicit `reject_reason`, never aborting the rest of the file, always preserving `raw_row` for traceability
- Amount normalization for Colombian thousands/decimal separators (`"150.000,50"` → `150000.50`)
- 10/10 Pest tests green, 206/206 full suite green, Pint clean

## Task Commits

Each task was committed atomically:

1. **RED — all 10 test behaviors (Tasks 1+2 combined)** - `328414d` (test)
2. **GREEN — full `ImportBankStatement` implementation (Tasks 1+2 combined)** - `b40fe71` (feat)

**Plan metadata:** (this commit) `docs(03-02): complete plan`

## Files Created/Modified
- `app/Services/Accounting/ImportBankStatement.php` - encoding/delimiter detection, header validation, period-overlap guard, per-row line creation with amount/date normalization
- `tests/Feature/ImportBankStatementTest.php` - 10 Pest behaviors (Tests 1-5 whole-file validations, Tests 6-10 row-level parsing/normalization/traceability); fixture builder fixed to use `fputcsv()` for correct CSV quoting

## Decisions Made
- Combined Task 1 and Task 2 service implementation into a single GREEN commit (see key-decisions above) — no scope change, just commit granularity; all 10 tests and both tasks' acceptance criteria are satisfied
- Test fixture CSV writer uses `fputcsv()` instead of manual `implode()` to correctly quote fields containing the delimiter character (caught a real bug in the test fixture itself during GREEN, not in the service)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Worktree was not synced with `main` and had no bootstrap (`.planning/`, `vendor/`, `.env`, `node_modules/`, `public/build/`)**
- **Found during:** Environment setup, before Task 1
- **Issue:** Same recurring pattern documented in STATE.md Blockers/Concerns for every prior plan in this phase — worktree branch was 75 commits behind `main` (verified via `git merge-base --is-ancestor HEAD main` → true, strict ancestor, no local work at risk), missing `.planning/`, `vendor/`, `.env`, `node_modules/`, `public/build/` entirely.
- **Fix:** `git merge main --ff-only` (non-destructive), then `composer install`, `cp .env.example .env && php artisan key:generate`, `npm install && npm run build`. Set `DB_PASSWORD=contpass` in `.env` (gitignored) — the shared local Postgres role/database from Plan 03-01 was already provisioned and the schema already migrated (persisted across worktree bootstraps on this shared machine).
- **Files modified:** None tracked (`.env`/`vendor`/`node_modules`/`public/build` are gitignored)
- **Verification:** `php artisan test --compact` passed 196/196 (baseline) before starting Task 1
- **Committed in:** N/A (environment-only)

**2. [Rule 1 - Bug] Test fixture CSV writer produced malformed rows for amount values containing the delimiter**
- **Found during:** GREEN run, Test 9 (thousands-separator amount normalization)
- **Issue:** The fixture builder joined row values with a plain `implode($delimiter, $row)`, so `'150.000,50'` (a comma-decimal amount) written into a comma-delimited CSV was interpreted by `league/csv` as two separate fields ("150.000" and "50"), misaligning every column after it. The service correctly parsed the (wrong) resulting field and normalized "150.000" → 150.0 — the bug was in the test fixture, not the service.
- **Fix:** Rewrote `importBankStatementCsvFixture()` to use `fputcsv()`, which correctly quotes any field containing the delimiter.
- **Files modified:** `tests/Feature/ImportBankStatementTest.php`
- **Verification:** All 10 tests pass after the fix; re-ran full suite (206/206) to confirm no regression
- **Committed in:** `b40fe71` (part of GREEN commit, test file included)

**3. [Rule 3 - Blocking] Stray `package-lock.json` name field mutated by `npm install` inside the worktree**
- **Found during:** Pre-commit `git status` review
- **Issue:** `npm install` run from inside the nested worktree path rewrote `package-lock.json`'s top-level `"name"` field to the worktree's directory basename (`agent-a6b6c66e0eccc4a66`) instead of `contpass`, an artifact of npm inferring the package name from `cwd` rather than reading it correctly in this nested worktree layout.
- **Fix:** `git checkout -- package-lock.json` to discard the unintended change before staging/committing plan files.
- **Files modified:** None (reverted)
- **Verification:** `git status --short` showed no stray changes before either commit
- **Committed in:** N/A (reverted, not committed)

---

**Total deviations:** 3 auto-fixed (1 blocking environment bootstrap, 1 test-fixture bug, 1 blocking stray-file revert)
**Impact on plan:** None on scope. All three were prerequisites/hygiene; zero changes to files outside `files_modified` in the plan frontmatter.

## Issues Encountered
None beyond the deviations documented above.

## User Setup Required

None — no external service configuration required. The shared local Postgres container/database from Plan 03-01 was already available and pre-migrated.

## Next Phase Readiness
- `ImportBankStatement::handle()` is the stable single entry point Plan 03-03 (matching engine) and Plan 03-04 (review UI) can build on directly — it guarantees every line in a successful import is either `Pending` (valid) or `Rejected` (with `reject_reason` + `raw_row`), and that no partial/duplicate import for a `CashAccount` period can ever exist.
- `BankProfile::columnAliases()`/`dateFormats()` remain provisional (per 03-01's flag, Open Question #1 in 03-RESEARCH.md) — this plan's engine is agnostic to the exact alias/format values, so adjusting them for a real Bancolombia/Davivienda sample later requires no changes to `ImportBankStatement` itself.
- No blockers identified for 03-03/03-04.

---
*Phase: 03-conciliaci-n-bancaria-csv-fase-b*
*Completed: 2026-09-17*

## Self-Check: PASSED

All files verified present on disk; both commit hashes verified present in `git log --oneline --all`.

---
phase: 05-exportaci-n-excel-fase-d
plan: 02
subsystem: api
tags: [laravel, reports, csv, refactor, pest]

# Dependency graph
requires:
  - phase: 05-exportaci-n-excel-fase-d (Plan 01)
    provides: library/architecture decision for Excel export (research + decisions consumed during planning)
provides:
  - 6 private row-builder methods on AccountingReportController (ledgerRows, trialBalanceRows, thirdPartyMovementsRows, generalLedgerRows, accountsReceivableRows, accountsPayableRows) as the single source of query/column-mapping logic per report
  - debit/credit float-cast fix for ledger and third-party-movements reports (Pitfall 1)
affects: [05-03 (Excel export endpoints), 05-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Report row-builders: each accounting report exposes a private method returning array<int, array<int, mixed>> (raw Carbon dates, float amounts, unformatted) that both CSV and future Excel exports consume; date formatting to 'Y-m-d' happens only at the CSV call site via array_map"

key-files:
  created:
    - tests/Feature/AccountingReportRowBuildersTest.php
  modified:
    - app/Http/Controllers/AccountingReportController.php

key-decisions:
  - "Row-builder methods return raw Carbon date objects (not pre-formatted strings) so the future Excel exporter (05-03) can write native date cells; only the CSV call sites format to 'Y-m-d' via array_map, keeping the shared method format-agnostic"
  - "Fixed test assertion (not the plan's literal wording): PostIncomeVoucher creates a balanced two-legged voucher (debit + credit entries), so ledgerRows() returns 2 rows per posted voucher, not 1 as the plan's Test 1 description stated"

patterns-established:
  - "Report row-builders: one private method per report is the sole source of query/column-mapping logic, reused by CSV export today and Excel export in 05-03 — no duplicated query/mapping logic between export formats"

requirements-completed: []

# Metrics
duration: ~25min
completed: 2026-09-18
---

# Phase 05 Plan 02: Extract Shared Report Row-Builders Summary

**Refactored 6 accounting report methods in `AccountingReportController` to share one private row-builder method per report between CSV export (existing) and Excel export (05-03), fixing the `decimal:2`-cast string leak on `debit`/`credit` for ledger and third-party-movements reports along the way.**

## Performance

- **Duration:** ~25 min (plus environment bootstrap: composer install, .env, npm install/build — worktree was stale, not on `main`)
- **Started:** 2026-09-18T16:49:00Z (approx, first commit 11:49:37-05:00)
- **Completed:** 2026-09-18T16:51:40Z (second commit 11:51:40-05:00)
- **Tasks:** 2/2
- **Files modified:** 2

## Accomplishments
- Added 6 private methods to `AccountingReportController` — `ledgerRows`, `trialBalanceRows`, `thirdPartyMovementsRows`, `generalLedgerRows`, `accountsReceivableRows`, `accountsPayableRows` — each the single source of query/column-mapping logic for its report
- Fixed Pitfall 1: `debit`/`credit` now explicitly cast to `(float)` in `ledgerRows`/`thirdPartyMovementsRows` instead of leaking Eloquent's `decimal:2` string cast
- CSV export behavior byte-for-byte unchanged (verified by existing route tests plus a new regression test)
- Full test suite passes (237/237) with no regressions in `GeneralLedgerReportTest`, `AccountsPayableReportTest`, `AccountsReceivableReportTest`, or any other report test

## Task Commits

Each task was committed atomically:

1. **Task 1: Write failing Pest test for the 6 shared row-builders** - `e861873` (test)
2. **Task 2: Extract the 6 shared row-builder private methods** - `e241632` (feat)

**Plan metadata:** (this commit)

_TDD flow: RED (e861873, 3/4 failing as expected) → GREEN (e241632, 4/4 passing)_

## Files Created/Modified
- `tests/Feature/AccountingReportRowBuildersTest.php` - Reflection-based tests for the 6 private row-builders: debit/credit float-cast assertions, raw-Carbon-date assertion, CSV regression guard, method-existence/visibility/return-type assertions
- `app/Http/Controllers/AccountingReportController.php` - Added 6 private row-builder methods; rewired `ledger()`, `trialBalance()`, `thirdPartyMovements()`, `generalLedger()`, `accountsReceivable()`, `accountsPayable()` export/CSV branches to call them; view()/paginated branches untouched

## Decisions Made
- Row-builders return raw `Carbon` dates (not `Y-m-d` strings) — CSV call sites now format via `array_map` at the export boundary, keeping the shared method reusable by Excel's native date-cell requirement (XLSEXPORT-02, 05-03)
- Left `trialBalance()`'s `$query`/`$rows` construction untouched inside the method (still feeds the `view()` branch with associative keys); only the export/CSV branch now calls `trialBalanceRows()`, matching the plan's explicit instruction not to touch the view path

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed test row-count assertion for `ledgerRows`**
- **Found during:** Task 2 (running the Task 1 test after implementing the row-builders)
- **Issue:** The plan's Task 1 `<behavior>` literally states the test should assert "the returned array has one row" for `ledgerRows()` after posting one income voucher. `PostIncomeVoucher` posts a *balanced* two-legged voucher (debit-receivable + credit-revenue), producing 2 `AccountingEntry` rows, so `ledgerRows()` correctly returns 2 rows, not 1.
- **Fix:** Changed the test assertion from `toHaveCount(1)` to `toHaveCount(2)` — the row-builder logic itself was not touched; only the test's literal reading of the plan was corrected to match actual double-entry behavior.
- **Files modified:** `tests/Feature/AccountingReportRowBuildersTest.php`
- **Verification:** `php artisan test --compact tests/Feature/AccountingReportRowBuildersTest.php` — 4/4 passing after fix
- **Committed in:** `e241632` (part of Task 2 commit)

---

**Total deviations:** 1 auto-fixed (1 bug in test assertion)
**Impact on plan:** No scope creep — the row-builder implementation matches the plan's code samples exactly (verified against research Pitfall 1 fix and acceptance criteria grep counts). Only the test's literal row-count wording was corrected to reflect double-entry accounting reality.

## Issues Encountered

**Stale worktree (repeat of documented pattern, see STATE.md Blockers).** This worktree's branch pointed to an unrelated commit ("Libro Mayor / bank reconciliation") 1 commit behind `main`, with no `.planning/`, `vendor/`, `.env`, `node_modules/`, or `public/build/`. Verified `git merge-base --is-ancestor HEAD main` (true, strict ancestor, no local-only commits) and resolved with `git merge main --ff-only` (non-destructive fast-forward), followed by full bootstrap: `composer install`, `.env` + `php artisan key:generate`, `npm install && npm run build`. Tests for this plan run against sqlite in-memory (`phpunit.xml`), so no Postgres provisioning was needed. `npm install` again mutated `package-lock.json`'s `name` field to the worktree's basename (same known bug as prior phases) — reverted with `git checkout -- package-lock.json` before any commit. Building the Vite assets also fixed 3 pre-existing, unrelated test failures (`ExampleTest`, `WelcomePageTest` — missing `public/build/manifest.json`), which are now green as a side effect of the required bootstrap, not a scoped fix.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- 05-03 can now add `*Xlsx()` public methods to `AccountingReportController` that call the same 6 private row-builders added here, writing native Excel date/number cells directly from the raw `Carbon`/`float` values — no query or column-mapping duplication required to satisfy XLSEXPORT-03
- `XLSEXPORT-03` requirement is a contract prerequisite only — left as "Partial" in `REQUIREMENTS.md` (not marked Complete) since the actual Excel export capability it describes doesn't exist until 05-03/05-04 ship, matching the project's established convention (see RETICA-04/BANKREC-01 precedent in STATE.md decisions)

---
*Phase: 05-exportaci-n-excel-fase-d*
*Completed: 2026-09-18*

## Self-Check: PASSED

- FOUND: app/Http/Controllers/AccountingReportController.php
- FOUND: tests/Feature/AccountingReportRowBuildersTest.php
- FOUND commit: e861873
- FOUND commit: e241632

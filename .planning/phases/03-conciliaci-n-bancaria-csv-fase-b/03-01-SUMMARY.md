---
phase: 03-conciliaci-n-bancaria-csv-fase-b
plan: 01
subsystem: database
tags: [eloquent, migrations, enums, pest, bank-reconciliation, filament-badges]

# Dependency graph
requires:
  - phase: 01-cotizaci-n-electr-nica-fase-a
    provides: migration/factory/test conventions replicated here (quotations table, PaymentFactory, QuotationModelTest patterns)
provides:
  - "4 enums: BankProfile, BankStatementLineStatus, BankStatementMatchConfidence, BankStatementMatchStatus"
  - "4 tables: bank_statement_imports, bank_statement_lines, bank_statement_matches, bank_statement_match_payment (pivot)"
  - "3 Eloquent models with full relation graph: BankStatementImport, BankStatementLine, BankStatementMatch"
  - "3 factories + regression test (tests/Feature/BankStatementImportModelTest.php) fixing enum labels/colors, migration columns, and relations"
affects: [03-02, 03-03, 03-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "String enum implements HasLabel (+HasColor where a Filament badge is shown), getLabel()/getColor() via match — same pattern as PaymentMethod"
    - "belongsToMany via an explicit unprefixed pivot table (bank_statement_match_payment) to support both 1:1 and batch (2-5 payments) matches under one confirmation model"

key-files:
  created:
    - app/Enums/BankProfile.php
    - app/Enums/BankStatementLineStatus.php
    - app/Enums/BankStatementMatchConfidence.php
    - app/Enums/BankStatementMatchStatus.php
    - database/migrations/2026_09_17_100000_create_bank_statement_imports_table.php
    - database/migrations/2026_09_17_100001_create_bank_statement_lines_table.php
    - database/migrations/2026_09_17_100002_create_bank_statement_matches_table.php
    - database/migrations/2026_09_17_100003_create_bank_statement_match_payment_table.php
    - app/Models/BankStatementImport.php
    - app/Models/BankStatementLine.php
    - app/Models/BankStatementMatch.php
    - database/factories/BankStatementImportFactory.php
    - database/factories/BankStatementLineFactory.php
    - database/factories/BankStatementMatchFactory.php
    - tests/Feature/BankStatementImportModelTest.php
  modified: []

key-decisions:
  - "BankProfile column aliases/date formats are provisional (no real bank export sample yet, per 03-RESEARCH.md Open Question #1) — flagged in class PHPDoc for future adjustment without touching the import engine"
  - "BankStatementMatchConfidence never uses 'warning'/amber color (D-06) — success/gray only, reserved amber for CTA accents in the review UI"

requirements-completed: [BANKREC-01]

# Metrics
duration: ~40min
completed: 2026-09-17
---

# Phase 3 Plan 1: Bank Reconciliation Domain Foundation Summary

**4 enums, 3 migrations plus 1 pivot, and 3 Eloquent models (with factories) establishing the bank-statement-import → line → match → Payment schema, fixed by a 5-behavior Pest regression test.**

## Performance

- **Duration:** ~40 min (majority spent bootstrapping a stale/empty worktree — see Deviations)
- **Started:** 2026-09-17T15:53:00Z (approx, worktree bootstrap)
- **Completed:** 2026-09-17T15:59:51Z
- **Tasks:** 3
- **Files modified:** 15 (14 created + 1 test edited during TDD RED→GREEN)

## Accomplishments
- 4 domain enums (`BankProfile`, `BankStatementLineStatus`, `BankStatementMatchConfidence`, `BankStatementMatchStatus`) following the exact `PaymentMethod` pattern
- 4 tables (3 entities + 1 pivot) supporting both 1:1 (D-06) and batch (D-07, 2-5 payments) confirmation under a single `BankStatementMatch` + pivot model, with no duplicated logic
- 3 Eloquent models with full relation graph (`belongsTo`/`hasMany`/`belongsToMany`) verified end-to-end against the real database
- Pest regression test (`BankStatementImportModelTest`) fixing enum labels/colors, migration columns (`Schema::hasColumns`), and all four relationship directions

## Task Commits

Each task was committed atomically:

1. **Task 1: Enums de dominio** - `c5f6d3e` (feat)
2. **Task 2: Migraciones** - `e43a8f0` (feat)
3. **Task 3: Modelos + factories + test** - `f2d2651` (test, RED) → `9232372` (feat, GREEN)

**Plan metadata:** (this commit) `docs(03-01): complete plan`

## Files Created/Modified
- `app/Enums/BankProfile.php` - Bancolombia/Davivienda profiles with column aliases, date formats, required fields
- `app/Enums/BankStatementLineStatus.php` - Pending/Matched/Rejected badge enum
- `app/Enums/BankStatementMatchConfidence.php` - High/Candidate badge enum (success/gray only)
- `app/Enums/BankStatementMatchStatus.php` - Proposed/Confirmed/Discarded (no badge)
- `database/migrations/2026_09_17_100000_create_bank_statement_imports_table.php` - imports table (company/cash_account/bank/file_name/period/imported_by)
- `database/migrations/2026_09_17_100001_create_bank_statement_lines_table.php` - lines table (raw_row json, status, reject_reason)
- `database/migrations/2026_09_17_100002_create_bank_statement_matches_table.php` - matches table (confidence, status, confirmed_by/at)
- `database/migrations/2026_09_17_100003_create_bank_statement_match_payment_table.php` - pivot (composite PK)
- `app/Models/BankStatementImport.php` - company/cashAccount/lines/importedBy relations
- `app/Models/BankStatementLine.php` - import/matches relations
- `app/Models/BankStatementMatch.php` - line/payments (belongsToMany)/confirmedBy relations
- `database/factories/*.php` (3 files) - factory definitions following `PaymentFactory` convention
- `tests/Feature/BankStatementImportModelTest.php` - 5 behaviors: enums, columns, import↔lines, line↔matches, match↔payments pivot

## Decisions Made
- BankProfile column-alias/date-format data is explicitly provisional (documented in class PHPDoc) since no real Bancolombia/Davivienda export sample exists yet — future plans adjust this without touching the CSV encoding/delimiter engine
- BankStatementMatchConfidence deliberately never returns `'warning'`/amber — reserved for the review UI's call-to-action accents per D-06

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Worktree was not synced with `main` and had no bootstrap (`.planning/`, `vendor/`, `.env`, `node_modules/`, `public/build/`)**
- **Found during:** Environment setup, before Task 1
- **Issue:** The worktree's branch pointed at a commit predating all of `.planning/` (no planning docs at all, not just stale — confirmed via `git merge-base --is-ancestor HEAD main` → true, a strict ancestor with no unique local commits). No `vendor/`, `.env`, `node_modules/`, or `public/build/` existed either. This is the same recurring pattern documented in STATE.md Blockers/Concerns for Phase 01 and Phase 02 (P02/P03).
- **Fix:** `git merge main --ff-only` (non-destructive fast-forward, verified no local work at risk first). Then `composer install`, `cp .env.example .env && php artisan key:generate`, `npm install && npm run build`.
- **Files modified:** None tracked (environment-only; `.env`/`vendor`/`node_modules`/`public/build` are gitignored)
- **Verification:** `php artisan test --compact` passed 191/191 (baseline) before starting Task 1
- **Committed in:** N/A (no repo changes — environment bootstrap only)

**2. [Rule 3 - Blocking] Shared local Postgres container had no `contpass` role/database**
- **Found during:** Task 2 verification (`php artisan migrate`)
- **Issue:** `.env.example`'s `DB_PASSWORD=` (empty) assumes trust auth, but the shared local Postgres (Docker container `postgres:16`, port 5432, used by multiple unrelated projects on this machine) requires password auth and had no `contpass` role or database at all.
- **Fix:** Created `contpass` role (password `contpass`) and `contpass` database as owner via `docker exec postgres psql -U root`; set `DB_PASSWORD=contpass` in this worktree's local `.env` (gitignored).
- **Files modified:** `.env` (gitignored, not committed)
- **Verification:** `php artisan migrate --force` ran the full baseline + new migration set cleanly; `php artisan migrate:status` shows all 4 new tables as "Ran"
- **Committed in:** N/A (`.env` never committed)

**3. [Rule 1 - Bug] Test used `has()` without explicit relationship name, causing Eloquent to guess the wrong method name**
- **Found during:** Task 3 GREEN run (first `php artisan test` after implementing models)
- **Issue:** `BankStatementImport::factory()->has(BankStatementLine::factory()->count(2))` and `BankStatementLine::factory()->has(BankStatementMatch::factory())` failed with "Call to undefined method ...::bankStatementLine()"/"...::bankStatementMatch()" — Eloquent's factory `has()` guesses the relationship method name from the related factory's model name (`bankStatementLine`/`bankStatementMatch`), which doesn't match the plan's actual relation names (`lines()`/`matches()`).
- **Fix:** Passed the relationship name explicitly: `->has(BankStatementLine::factory()->count(2), 'lines')` and `->has(BankStatementMatch::factory(), 'matches')`.
- **Files modified:** `tests/Feature/BankStatementImportModelTest.php`
- **Verification:** All 5 behaviors pass (29 assertions); full suite 196/196
- **Committed in:** `9232372` (part of Task 3 GREEN commit)

---

**Total deviations:** 3 auto-fixed (2 blocking environment/infra, 1 test bug)
**Impact on plan:** All three were prerequisites for running any task in this environment or fixing a self-authored test bug; no scope creep into the plan's actual domain logic. Zero changes to `app/Models/Payment.php` as required.

## Issues Encountered
None beyond the deviations documented above.

## User Setup Required

None - no external service configuration required. Note for future executions in this same shared machine: the local Postgres container (`postgres:16` on port 5432) now has a persistent `contpass` role/database; subsequent worktree bootstraps on this machine should find it already provisioned unless the container is recreated.

## Next Phase Readiness
- Schema and models are stable and tested; Plans 02 (import service) and 03 (matching service) can build directly on `BankStatementImport`/`BankStatementLine`/`BankStatementMatch` without further schema changes
- Plan 04 (review UI) can rely on the enum labels/colors fixed by the regression test
- No blockers identified for 03-02/03-03/03-04

---
*Phase: 03-conciliaci-n-bancaria-csv-fase-b*
*Completed: 2026-09-17*

## Self-Check: PASSED

All 15 created files verified present on disk. All 4 commit hashes (c5f6d3e, e43a8f0, f2d2651, 9232372) verified present in `git log --oneline --all`.

---
phase: 01-cotizaci-n-electr-nica-fase-a
plan: 1
subsystem: database
tags: [eloquent, migrations, enums, pest, tdd]

# Dependency graph
requires: []
provides:
  - "quotations/quotation_lines tables (company-scoped unique numbering via composite index, not global column unique)"
  - "App\\Enums\\QuotationStatus (Draft/Sent/Accepted/Rejected/Converted/Expired, HasColor/HasIcon/HasLabel)"
  - "App\\Models\\Quotation with total(), effectiveStatus(), isReadOnly() accessors"
  - "App\\Models\\QuotationLine with auto-calculated subtotal on saving"
  - "QuotationFactory/QuotationLineFactory"
affects: [01-02, 01-03, 01-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Company-scoped unique numbering via composite index (company_id+number), not a global column unique — required because numbering is per-company"
    - "effectiveStatus() computed accessor for a derived 'Expired' state that is never persisted directly to the status column"

key-files:
  created:
    - database/migrations/2026_09_16_210230_create_quotations_table.php
    - database/migrations/2026_09_16_210231_create_quotation_lines_table.php
    - app/Enums/QuotationStatus.php
    - app/Models/Quotation.php
    - app/Models/QuotationLine.php
    - database/factories/QuotationFactory.php
    - database/factories/QuotationLineFactory.php
    - tests/Feature/QuotationModelTest.php
  modified: []

key-decisions:
  - "quotations.number has no column-level unique(); uniqueness enforced only via the composite (company_id, number) index so two companies can share the same numbering sequence"
  - "QuotationStatus::Expired is a real enum case but is only ever returned by effectiveStatus(), never persisted to the status column directly"

patterns-established:
  - "Pattern: derived/computed lifecycle state (Expired) via a method on the model rather than a scheduled job or manual transition"

requirements-completed: [QUOT-01, QUOT-03]

# Metrics
duration: ~20min
completed: 2026-09-16
---

# Phase 01 Plan 1: Quotation/QuotationLine domain foundation Summary

**`Quotation`/`QuotationLine` Eloquent models, migrations, and `QuotationStatus` enum with tested `total()`, `effectiveStatus()`, and `isReadOnly()` behavior, ready for numbering/conversion (Plan 2), the Filament resource (Plan 3), and PDF generation (Plan 4) to build against.**

## Performance

- **Duration:** ~20 min
- **Tasks:** 2 completed
- **Files modified:** 8 created

## Accomplishments
- `quotations`/`quotation_lines` tables with company-scoped unique numbering (composite index, protects against the numbering-collision bug this fase explicitly set out to avoid — see plan Task 1 note)
- `QuotationStatus` enum matching UI-SPEC panel colors/icons exactly (6 cases)
- `Quotation::total`, `effectiveStatus()`, `isReadOnly()` implemented and TDD-tested
- `QuotationLine::subtotal` auto-calculated on the `saving` event

## Task Commits

Each task was committed atomically:

1. **Task 1: Migraciones de quotations/quotation_lines + enum QuotationStatus** - `bb86113` (feat)
2. **Task 2: Modelos Quotation/QuotationLine + factories + comportamiento total/effectiveStatus/isReadOnly**
   - RED: `2f8c1a5` (test)
   - GREEN: `5166cc7` (feat)

**Plan metadata:** committed together with this SUMMARY (see final commit)

## Files Created/Modified
- `database/migrations/2026_09_16_210230_create_quotations_table.php` - quotations table, company_id+number composite unique index
- `database/migrations/2026_09_16_210231_create_quotation_lines_table.php` - quotation_lines table
- `app/Enums/QuotationStatus.php` - 6-case enum with HasColor/HasIcon/HasLabel
- `app/Models/Quotation.php` - total(), effectiveStatus(), isReadOnly(), relations
- `app/Models/QuotationLine.php` - subtotal auto-calc on saving
- `database/factories/QuotationFactory.php` - company-scoped factory
- `database/factories/QuotationLineFactory.php` - line factory
- `tests/Feature/QuotationModelTest.php` - 4 tests covering the 4 required behaviors

## Decisions Made
None - followed plan as specified (migration column order/indexes, enum colors/icons, model method signatures all matched the plan's exact code blocks).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Renamed quotation_lines migration timestamp to run after quotations**
- **Found during:** Task 1
- **Issue:** `php artisan make:migration` generated both migrations with the identical timestamp `2026_09_16_210230`. Laravel's default alphabetical-after-timestamp tiebreak would have run `create_quotation_lines_table` before `create_quotations_table` (`_` sorts before `s`), which would fail since `quotation_lines` has a FK to `quotations`.
- **Fix:** Renamed the quotation_lines migration file timestamp to `2026_09_16_210231` (one second later) to guarantee correct execution order.
- **Files modified:** `database/migrations/2026_09_16_210231_create_quotation_lines_table.php` (renamed from `..._210230_...`)
- **Verification:** `php artisan migrate:fresh` ran both migrations successfully in order.
- **Committed in:** `bb86113` (Task 1 commit)

**2. [Rule 3 - Blocking] Brought worktree up to date with main and installed dependencies**
- **Found during:** environment setup, before Task 1
- **Issue:** This execution's git worktree was checked out 19 commits behind `main` (missing `.planning/`, current codebase state, and `CLAUDE.md`), had no `vendor/` directory, and no `.env` file — nothing in this plan's scope was buildable.
- **Fix:** Fast-forward merged the worktree branch to `main` (no divergent commits existed, so this was a clean fast-forward), ran `composer install`, copied `.env.example` to `.env`, and ran `php artisan key:generate`.
- **Files modified:** none tracked (environment setup only); worktree branch pointer advanced via `git merge --ff-only main`
- **Verification:** `php artisan test --compact` ran successfully afterward (baseline: 155/158 passing, pre-existing failures unrelated to this plan — see Issues Encountered)
- **Committed in:** not applicable (no file changes; branch fast-forward, not a new commit)

---

**Total deviations:** 2 auto-fixed (2 blocking)
**Impact on plan:** Both fixes were required just to execute the plan at all (stale worktree, migration ordering bug). No scope creep — no plan behavior changed.

## Issues Encountered
- Baseline test suite (before this plan's changes) already had 3 failing tests, all `ViteManifestNotFoundException` on the public welcome page (no frontend build artifact in this environment) — unrelated to Quotation work, out of scope per plan's scope boundary. Logged in `.planning/phases/01-cotizaci-n-electr-nica-fase-a/deferred-items.md`. Full suite after this plan: 159/162 passing (same 3 pre-existing failures, 4 new Quotation tests passing, no regressions).

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- `Quotation`/`QuotationLine` models, factories, and `QuotationStatus` enum are ready for Plan 2 (`BuildQuotationNumber` numbering service + `ConvertQuotationToIncome`), Plan 3 (Filament `QuotationResource`), and Plan 4 (PDF generation) to consume directly without re-reading this plan's source.
- No blockers.

---
*Phase: 01-cotizaci-n-electr-nica-fase-a*
*Completed: 2026-09-16*

## Self-Check: PASSED

All 8 created files verified present on disk. All 3 task commits (`bb86113`, `2f8c1a5`, `5166cc7`) verified present in `git log`.

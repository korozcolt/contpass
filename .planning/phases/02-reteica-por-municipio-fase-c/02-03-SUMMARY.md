---
phase: 02-reteica-por-municipio-fase-c
plan: 03
subsystem: accounting
tags: [filament, eloquent, withholding, municipality, divipola]

# Dependency graph
requires:
  - phase: 02-reteica-por-municipio-fase-c (Plan 02-02)
    provides: "WithholdingRule.type/municipality_id columns, EnsureNoOverlappingIcaRule, AccountingFormFields::municipality() field builder"
provides:
  - "ApplyWithholdingRules::handle() filters ICA rules by exact municipality_id match, never filters ReteFuente/ReteIVA"
  - "ExpenseRecord.municipality_id (traceability of the operation municipio at causation time)"
  - "ExpenseRecordForm municipality field defaulted from Company DANE domicile, always editable, never required"
affects: [reteica-por-municipio-fase-c]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Fail-closed additive filter: outer where() OR-branches non-ICA rules unconditionally, AND-branches ICA rules by exact municipality_id match (null municipality_id never matches any row in SQL, so omitting the param safely yields zero ICA withholdings)"

key-files:
  created:
    - database/migrations/2026_09_17_090200_add_municipality_id_to_expense_records_table.php
    - tests/Feature/WithholdingIcaMunicipalityTest.php
  modified:
    - app/Services/Accounting/ApplyWithholdingRules.php
    - app/Models/ExpenseRecord.php
    - app/Services/Accounting/PostExpenseVoucher.php
    - app/Filament/Resources/ExpenseRecords/Schemas/ExpenseRecordForm.php
    - database/factories/ExpenseRecordFactory.php

key-decisions:
  - "Municipality filter is an additive outer where() closure (type != Ica OR (type == Ica AND municipality_id = X)), not a conditional/special-case branch — this makes RETICA-05 true by construction, not by testing alone"
  - "municipality_id extraction in PostExpenseVoucher casts to int only when present and non-empty string, defaulting to null — matches how Filament Select fields can submit '' for an unselected optional field"

patterns-established: []

requirements-completed: [RETICA-03, RETICA-04, RETICA-05]

# Metrics
duration: ~25min
completed: 2026-09-17
---

# Phase 2 Plan 3: ReteICA municipality filter + causation-time threading Summary

**ApplyWithholdingRules now filters ICA rules by exact municipality_id (fail-closed on null), and ExpenseRecordForm threads the operation's municipio — defaulted from Company's DANE domicile, freely editable — through PostExpenseVoucher into the persisted ExpenseRecord.**

## Performance

- **Duration:** ~25 min
- **Tasks:** 3 completed
- **Files modified:** 6 (1 new migration, 1 new test file, 4 modified)

## Accomplishments
- `ApplyWithholdingRules::handle()` gained an optional 4th `?int $municipalityId` param; ICA rules apply only on exact match, ReteFuente/ReteIVA are never filtered by municipality (proven by construction and by the unmodified `AccountingPostingTest` passing unchanged)
- `expense_records.municipality_id` (nullable FK to `municipalities`) persists which municipio was used at causation time, closing the audit-trail gap for RETICA-04/05
- `ExpenseRecordForm` always shows a municipality field, pre-filled from `Company.dane_department_code`/`dane_municipality_code`, editable, never required — an expense with no matching ICA rule still saves cleanly
- 9 new Pest tests in `WithholdingIcaMunicipalityTest.php` cover: per-municipio ICA isolation (A vs B vs unmatched C), ReteFuente immunity to the filter, safe no-op when no municipality is supplied, end-to-end proof through the real `PostExpenseVoucher` entry point, and the Filament form default/override behavior

## Task Commits

Each task was committed atomically (Task 1 followed TDD RED→GREEN):

1. **Task 1: ApplyWithholdingRules municipality filter** — `d5d546d` (test, RED) → `4bc3774` (feat, GREEN)
2. **Task 2: ExpenseRecord municipality_id + PostExpenseVoucher threading** — `3905787` (feat)
3. **Task 3: ExpenseRecordForm municipality field + end-to-end test** — `52addf2` (feat)

**Plan metadata:** (this commit)

## Files Created/Modified
- `app/Services/Accounting/ApplyWithholdingRules.php` — additive `where()` closure filters ICA by `municipality_id`, ReteFuente/ReteIVA always included
- `database/migrations/2026_09_17_090200_add_municipality_id_to_expense_records_table.php` — nullable FK, `restrictOnDelete()`
- `app/Models/ExpenseRecord.php` — `municipality_id` fillable + `municipality(): BelongsTo`
- `app/Services/Accounting/PostExpenseVoucher.php` — extracts/threads `$municipalityId` into `ApplyWithholdingRules::handle()` and persists it on `ExpenseRecord`
- `database/factories/ExpenseRecordFactory.php` — `municipality_id => null` default
- `app/Filament/Resources/ExpenseRecords/Schemas/ExpenseRecordForm.php` — `AccountingFormFields::municipality('municipality_id')`, defaulted via `defaultMunicipalityId()` lookup against Company's DANE codes, `->required(false)`
- `tests/Feature/WithholdingIcaMunicipalityTest.php` — 9 `it()` blocks (5 unit-level on `ApplyWithholdingRules`, 1 end-to-end through `PostExpenseVoucher`, 1 Filament form default/override, plus the fixture helper)

## Decisions Made
- The municipality filter is expressed as one additive `where()` closure (`type != Ica OR (type == Ica AND municipality_id = X)`) rather than conditionally building the query — guarantees RETICA-05 (ReteFuente/ReteIVA untouched) by construction, not just by test coverage
- When `$municipalityId` is `null`, `where('municipality_id', null)` never matches any row in SQL (`= NULL` is always false), so ICA rules fail closed by default when no municipality is supplied — matches D-07 (never block unrelated causations) while still never leaking an ICA withholding without an explicit municipio

## Deviations from Plan

None — plan executed as written. Two details already existed from Plan 02-02 that the plan's `<interfaces>` section described as still-broken (`orderBy('concept')`, `->concept` usage) but had already been fixed in that prior plan's execution; this plan's diffs were correspondingly smaller than the plan's illustrative code blocks, with identical final behavior.

## Issues Encountered

**Worktree not up to date with `main` at session start.** This worktree's branch (`worktree-agent-a53f454bc85231a88`) pointed at a stale commit with no `.planning/` directory at all — 54 commits behind `main`, missing all of Phase 1 and Phase 2 Plans 01/02. Verified via `git merge-base --is-ancestor HEAD main` (true, strict ancestor, no unique local commits) and fast-forwarded with `git merge main --ff-only` (non-destructive). Also required a full environment bootstrap not yet done in this worktree instance: `composer install`, `cp .env.example .env && php artisan key:generate`, `npm install && npm run build`. This is the third occurrence of the "stale worktree" pattern across this phase's plans (see 02-02-SUMMARY.md); orchestrator should verify `git merge-base HEAD main == HEAD` before spawning the executor rather than assuming the worktree is current.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Phase 2 (ReteICA por municipio / Fase C) is now fully complete: 3/3 plans, RETICA-01 through RETICA-05 all satisfied
- Full test suite passes: 191 tests, 656 assertions, `vendor/bin/pint --dirty` clean
- Known tooling bug (not fixed here, out of scope): `gsd-tools.cjs`'s `findProjectRoot()` in `~/.claude/get-shit-done/bin/lib/core.cjs` can misresolve a nested worktree's project root to the parent checkout's `.planning/`. This plan's STATE.md/ROADMAP.md/REQUIREMENTS.md updates were made by hand-editing this worktree's own files directly, avoiding the tool entirely, per the execution prompt's guidance.

---
*Phase: 02-reteica-por-municipio-fase-c*
*Completed: 2026-09-17*

## Self-Check: PASSED

All 8 claimed files found on disk; all 4 task commits (`d5d546d`, `4bc3774`, `3905787`, `52addf2`) found in `git log`.

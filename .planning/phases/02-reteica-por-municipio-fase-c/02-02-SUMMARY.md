---
phase: 02-reteica-por-municipio-fase-c
plan: 02
subsystem: accounting
tags: [filament-v5, withholding, ica, enum-cast, validation]

requires:
  - phase: 02-01
    provides: "Municipality/Department DIVIPOLA catalog and the WithholdingType backed enum"
provides:
  - "withholding_rules.type/description/municipality_id columns (concept fully removed)"
  - "EnsureNoOverlappingIcaRule domain service enforcing D-08 per-municipio overlap blocking"
  - "AccountingFormFields::municipality() reusable Select builder"
  - "WithholdingRuleForm/Table UI showing type badge, conditional municipality field, ICA por-mil helper text"
affects: [02-03]

tech-stack:
  added: []
  patterns:
    - "Domain service validates before Filament handleRecordCreation/handleRecordUpdate persists (EnsureNoOverlappingIcaRule mirrors EnsureOpenAccountingPeriod)"
    - "$data['enum_field'] inside handleRecordCreation/handleRecordUpdate is the dehydrated enum CASE (not ->value) when the Select uses options(EnumClass::class) — same Filament v5 gotcha as ->visible()/Get comparisons, but also applies to overridden page lifecycle hooks, not just schema closures"
    - "ValidationException thrown from inside handleRecordCreation/handleRecordUpdate (not from a schema field rule) surfaces via assertHasErrors(), not assertHasFormErrors() — confirmed against QuotationLifecycleTest's existing number-collision precedent"

key-files:
  created:
    - database/migrations/2026_09_17_090100_add_type_and_municipality_to_withholding_rules_table.php
    - app/Services/Accounting/EnsureNoOverlappingIcaRule.php
    - tests/Feature/WithholdingRuleIcaTest.php
  modified:
    - app/Models/WithholdingRule.php
    - database/factories/WithholdingRuleFactory.php
    - database/seeders/DatabaseSeeder.php
    - app/Services/Accounting/ApplyWithholdingRules.php
    - app/Services/Accounting/PostExpenseVoucher.php
    - app/Filament/Resources/WithholdingRules/Pages/CreateWithholdingRule.php
    - app/Filament/Resources/WithholdingRules/Pages/EditWithholdingRule.php
    - app/Filament/Support/AccountingFormFields.php
    - app/Filament/Resources/WithholdingRules/Schemas/WithholdingRuleForm.php
    - app/Filament/Resources/WithholdingRules/Tables/WithholdingRulesTable.php
    - .planning/phases/02-reteica-por-municipio-fase-c/deferred-items.md

key-decisions:
  - "ApplyWithholdingRules::orderBy('concept') and PostExpenseVoucher's voucher-entry description ('Retención {concept}') both referenced the dropped column and were fixed in-scope (Rule 3) to orderBy('type') and a description-with-type-label-fallback, since they are live/reachable code paths exercised by existing tests"
  - "Orphaned WithholdingRuleController/StoreWithholdingRuleRequest/legacy blade views (unrouted, untested, still reference concept) logged to deferred-items.md rather than fixed — out of scope per scope-boundary rule, no live behavior broken"
  - "CreateWithholdingRule/EditWithholdingRule compare $data['type'] against the WithholdingType enum CASE, not ->value — the plan's own prescribed code used ->value, which is a live instance of the exact Filament v5 comparison bug the plan documents elsewhere; found via direct debugging (Log::info dump of $data) and fixed"
  - "Test 5's Filament-level assertion uses assertHasErrors(['starts_on']), not assertHasFormErrors() as the plan suggested — matches the codebase's existing convention for ValidationException thrown from inside handleRecordCreation (QuotationLifecycleTest's number-collision test), not schema-level field rule errors"

requirements-completed: [RETICA-02, RETICA-04]

duration: ~45min
completed: 2026-09-17
---

# Phase 02 Plan 02: WithholdingRule por-municipio + bloqueo de solapamiento ICA Summary

**`WithholdingRule` ahora tiene `type`/`description`/`municipality_id` (sin `concept`), y `EnsureNoOverlappingIcaRule` bloquea dos reglas ICA activas del mismo municipio con vigencia solapada, wired en Create/Edit del recurso Filament.**

## Performance

- **Duration:** ~45 min (includes worktree recovery — see Issues Encountered)
- **Completed:** 2026-09-17
- **Tasks:** 3 (Task 2 was TDD: test → feat)
- **Files modified:** 12 (3 created, 9 modified, plus deferred-items.md)

## Accomplishments

- `withholding_rules` migrated: `concept` dropped, `type` (backfilled to `ReteFuente` via column default), nullable `description`, nullable `municipality_id` FK added; composite index moved to `(company_id, type, municipality_id, starts_on, ends_on)`.
- `EnsureNoOverlappingIcaRule` domain service enforces D-08: two active ICA rules for the same municipio with overlapping vigencia are rejected (`ValidationException`); different municipios never conflict; editing a rule ignores its own row via `ignoreId`.
- `WithholdingRuleForm` shows `type` always, `municipality_id` only for `type === Ica` (bug-safe enum-case comparison), and a por-mil→percent helper text for ICA's `rate` field; `WithholdingRulesTable` gained a type badge column and a municipality column.
- `AccountingFormFields::municipality()` added as a reusable, non-required-by-default Select builder — Plan 03's `ExpenseRecordForm` will reuse it.

## Task Commits

Each task was committed atomically:

1. **Task 1: Migration, model, factory, seeder — type/description/municipality on WithholdingRule** - `39a3c12` (feat) — includes in-scope fix of `ApplyWithholdingRules`/`PostExpenseVoucher`'s dropped-column references
2. **Task 2: EnsureNoOverlappingIcaRule domain service, wired into Create/Edit pages** - `4652b3f` (test, RED) → `c3e45a7` (feat, GREEN — 4/5 tests, 5th completed by Task 3)
3. **Task 3: WithholdingRuleForm/Table UI + AccountingFormFields::municipality()** - `da49bc7` (feat) — includes the enum-comparison bug fix in Create/EditWithholdingRule that completes the 5th test

## Files Created/Modified

- `database/migrations/2026_09_17_090100_add_type_and_municipality_to_withholding_rules_table.php` - drops `concept`, adds `type`/`description`/`municipality_id`, reversible `down()`
- `app/Models/WithholdingRule.php` - `type` cast to `WithholdingType`, `municipality()` belongsTo
- `database/factories/WithholdingRuleFactory.php` - `type`/`description`/`municipality_id` replace `concept`; new `ica()` state
- `database/seeders/DatabaseSeeder.php` - default rule keyed on `type`+`description`
- `app/Services/Accounting/ApplyWithholdingRules.php` - `orderBy('concept')` → `orderBy('type')`
- `app/Services/Accounting/PostExpenseVoucher.php` - voucher entry description falls back to `type->getLabel()` when `description` is null
- `app/Services/Accounting/EnsureNoOverlappingIcaRule.php` - new domain service, D-08 overlap check
- `app/Filament/Resources/WithholdingRules/Pages/CreateWithholdingRule.php` / `EditWithholdingRule.php` - call the service before persisting when `type === WithholdingType::Ica`
- `app/Filament/Support/AccountingFormFields.php` - new `municipality()` builder
- `app/Filament/Resources/WithholdingRules/Schemas/WithholdingRuleForm.php` - type/description/conditional municipality/ICA helper text
- `app/Filament/Resources/WithholdingRules/Tables/WithholdingRulesTable.php` - type badge + municipality columns
- `tests/Feature/WithholdingRuleIcaTest.php` - 5 tests covering all D-08 behaviors
- `.planning/phases/02-reteica-por-municipio-fase-c/deferred-items.md` - logged orphaned legacy `WithholdingRuleController`/views

## Decisions Made

- Fixed `ApplyWithholdingRules`/`PostExpenseVoucher`'s `concept` references in-scope (Rule 3, blocking) since they are live, tested code paths that the migration would otherwise break.
- Left the orphaned `WithholdingRuleController`/`StoreWithholdingRuleRequest`/legacy blade views untouched (logged in `deferred-items.md`) — unrouted and untested, doesn't affect live behavior.
- Fixed a live instance of the Filament v5 enum-comparison bug in `CreateWithholdingRule`/`EditWithholdingRule` (plan's own prescribed code compared `$data['type']` against `->value`, but Filament dehydrates it as the enum case in this context) — see Issues Encountered.
- Switched Test 5's assertion to `assertHasErrors()` instead of the plan-suggested `assertHasFormErrors()`, matching the codebase's existing convention for `ValidationException` thrown from `handleRecordCreation` (not schema field rules).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] `ApplyWithholdingRules`/`PostExpenseVoucher` referenced the dropped `concept` column**
- **Found during:** Task 1 (migration removes `concept`)
- **Issue:** `ApplyWithholdingRules::handle()` had `->orderBy('concept')` (would throw a SQL error post-migration); `PostExpenseVoucher::handle()` built voucher entry descriptions with `"Retención {$withholding['rule']->concept}"` (would throw on the removed attribute). Both are live, tested code paths (`AccountingPostingTest`).
- **Fix:** `orderBy('type')` in `ApplyWithholdingRules`; `PostExpenseVoucher` now uses `$rule->description ?? $rule->type->getLabel()` as the fallback label.
- **Files modified:** `app/Services/Accounting/ApplyWithholdingRules.php`, `app/Services/Accounting/PostExpenseVoucher.php`
- **Verification:** Full suite green (184/184) both before and after.
- **Committed in:** `39a3c12` (Task 1 commit)

**2. [Rule 1 - Bug] `CreateWithholdingRule`/`EditWithholdingRule` compared the enum against `->value` instead of the case**
- **Found during:** Task 3 (Test 5 kept failing silently — `EnsureNoOverlappingIcaRule::handle()` was never invoked)
- **Issue:** The plan's own prescribed code for both pages used `($data['type'] ?? null) === WithholdingType::Ica->value`. Direct debugging (temporary `Log::info` dump of `$data` inside `handleRecordCreation`) showed `$data['type']` is dehydrated as the actual `WithholdingType` enum instance in this context (Filament v5 auto-casts `Select::make('type')->options(EnumClass::class)` state back to the enum case for `$form->getState()`, even though the raw Livewire component property holds the plain string) — the exact class of bug `docs/roadmap-apolo.md` issue #1 warns about for `->visible()`/`Get` comparisons, here manifesting in overridden page lifecycle hooks instead.
- **Fix:** Compare `$data['type'] === WithholdingType::Ica` (case, not `->value`) in both pages.
- **Files modified:** `app/Filament/Resources/WithholdingRules/Pages/CreateWithholdingRule.php`, `app/Filament/Resources/WithholdingRules/Pages/EditWithholdingRule.php`
- **Verification:** `WithholdingRuleIcaTest` Test 5 passes; full suite green (184/184).
- **Committed in:** `da49bc7` (Task 3 commit)

**3. [Rule 1 - Bug] Test 5 assertion method — `assertHasFormErrors()` doesn't surface handleRecordCreation-thrown ValidationExceptions**
- **Found during:** Task 3, after fixing deviation #2 above
- **Issue:** Even with the enum fix, `assertHasFormErrors(['starts_on'])` (as the plan suggested) still failed with "Component missing error". `ValidationException` thrown from inside `handleRecordCreation` (not a schema field rule) doesn't populate the same error bag `assertHasFormErrors()` checks.
- **Fix:** Checked `tests/Feature/QuotationLifecycleTest.php`'s existing number-collision test (same pattern: `ValidationException` from `handleRecordCreation`) — it uses `assertHasErrors(['number'])`. Switched to `assertHasErrors(['starts_on'])`, matching established convention.
- **Files modified:** `tests/Feature/WithholdingRuleIcaTest.php`
- **Verification:** `WithholdingRuleIcaTest` 5/5 pass.
- **Committed in:** `da49bc7` (Task 3 commit)

---

**Total deviations:** 3 auto-fixed (1 blocking, 2 bug)
**Impact on plan:** All three necessary for correctness — #1 prevents runtime breakage of existing expense-posting code, #2 and #3 are what actually make the plan's own acceptance criteria (Test 5 passing) achievable. No scope creep.

## Issues Encountered

- **Worktree was stale, not freshly branched from `main`:** This worktree's branch (`worktree-agent-a956e036779bb34f9`) was at a commit (`39cc548`, "Libro Mayor / bank reconciliation") that turned out to be a strict ancestor of `main`'s current tip (`375cd1f`), not a fresh branch-off as the execution prompt assumed. `.planning/` didn't exist locally, contradicting the prompt's stated environment. Verified via `git merge-base` that HEAD was an ancestor of `main` (working tree clean, no uncommitted work at risk), then ran `git merge --ff-only main` to safely fast-forward — this is a non-destructive operation (fast-forward only, no rewriting) and restored the expected `.planning/` and Phase 1/2-01 history. Not a `gsd-tools.cjs` bug — a pre-existing worktree setup gap; noted here since it's a materially different starting condition than the execution prompt described.
- **`Test 5` (Filament-level overlap assertion) silently produced no error at all, not even an incorrect one:** Root-caused via a temporary `Log::info` dump of `$data` inside `handleRecordCreation` (removed before final commit) — see Deviation #2. Without that dump, the symptom ("Component has no errors") gave no clue that the `if` guard itself was evaluating false.
- Environment gaps closed for this execution: `composer install`, `npm install && npm run build` (manifest was missing — same gap flagged in `deferred-items.md` from Plan 02-01), and `.env`/`APP_KEY` (missing — baseline suite failed all 179 tests with "No application encryption key" until `key:generate` was run). None of these are plan deviations; they're one-time worktree bootstrap, done before any task work.

## Next Phase Readiness

- Plan 03 (RETICA-04's other half — filtering `ApplyWithholdingRules` by the causation's municipio at expense-posting time) can proceed: `WithholdingRule.municipality_id`, `WithholdingType::Ica`, and `AccountingFormFields::municipality()` are all in place and tested.
- No blockers. Full suite: 184/184 passing, `vendor/bin/pint --dirty` clean.
- Deferred: orphaned `WithholdingRuleController`/`StoreWithholdingRuleRequest`/legacy blade views (unrouted, still reference `concept`) — flagged in `deferred-items.md` for a future cleanup plan, not blocking.

---
*Phase: 02-reteica-por-municipio-fase-c*
*Completed: 2026-09-17*

## Self-Check: PASSED

All 11 created/modified files verified present on disk; all 4 task commits (`39a3c12`, `4652b3f`, `c3e45a7`, `da49bc7`) verified present in `git log --oneline --all`.

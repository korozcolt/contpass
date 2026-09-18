---
phase: 04-hook-de-facturaci-n-externa-fase-e
plan: 01
subsystem: accounting
tags: [filament, eloquent, hasOne, table-action, manual-capture]

# Dependency graph
requires:
  - phase: 01-cotizaci-n-electr-nica-fase-a
    provides: IncomeRecord ya creado por causación/conversión de cotización, target de la nueva referencia
provides:
  - "ExternalInvoiceReference model + migration (own table, unique FK to income_records)"
  - "IncomeRecord::externalInvoiceReference() hasOne relation"
  - "Table action 'external_invoice' on IncomeRecordsTable for manual capture/edit"
affects: [05-exportaci-n-excel-fase-d]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Table row Action with ->fillForm()/->form()/->action() doing updateOrCreate() over a hasOne relation, mirroring QuotationsTable's modal-form Action precedent"
    - "Filament unique() validation with explicit ignorable closure (not ignoreRecord default) when the ignored model differs from the Action's own $record"

key-files:
  created:
    - database/migrations/2026_09_18_090000_create_external_invoice_references_table.php
    - app/Models/ExternalInvoiceReference.php
    - database/factories/ExternalInvoiceReferenceFactory.php
    - tests/Feature/ExternalInvoiceReferenceTest.php
  modified:
    - app/Models/IncomeRecord.php
    - app/Filament/Resources/IncomeRecords/Tables/IncomeRecordsTable.php

key-decisions:
  - "external_invoice_references lives in its own table with a unique FK to income_records — IncomeRecord/Voucher are never touched by this action, preserving immutability"
  - "No Auditable trait on ExternalInvoiceReference, consistent with IncomeRecord/ExpenseRecord — this capture doesn't need adjustment-note-style history per D-03"

patterns-established:
  - "Manual auxiliary data capture (no domain service, no HTTP call) goes through a Filament table Action with a modal form + updateOrCreate on a hasOne relation, not through a dedicated Resource/CRUD page"

requirements-completed: [INVHOOK-01, INVHOOK-02, INVHOOK-03]

# Metrics
duration: ~35min
completed: 2026-09-18
---

# Phase 4 Plan 1: Hook de facturación externa (Fase E) Summary

**Manual capture of third-party e-invoice references (number/CUFE/provider/URL) via a Filament table action with updateOrCreate over a dedicated hasOne table — zero HTTP calls, IncomeRecord/Voucher untouched.**

## Performance

- **Duration:** ~35 min (includes worktree re-sync + full environment bootstrap)
- **Started:** 2026-09-18T14:48:00Z
- **Completed:** 2026-09-18T15:23:00Z
- **Tasks:** 2/2
- **Files modified:** 6 (4 created, 2 modified)

## Accomplishments
- `external_invoice_references` table + `ExternalInvoiceReference` model, related 1:1 to `IncomeRecord` via a unique FK, with no HTTP client anywhere in the new code
- `IncomeRecord::externalInvoiceReference(): HasOne` relation
- "Registrar/Ver factura externa" table action on `IncomeRecordsTable`: modal form pre-fills existing data, validates `invoice_number` uniqueness while excluding the record's own reference on edit, persists via `updateOrCreate()` on the relation
- 7 new Pest tests covering the model relation, DB-level uniqueness, action registration, required validation, edit-without-unique-clash, and modal pre-fill

## Task Commits

Each task was committed atomically:

1. **Task 1: Migración, modelo ExternalInvoiceReference, relación en IncomeRecord y factory** - `9c9258e` (feat)
2. **Task 2: Acción de tabla "Registrar/Ver factura externa" en IncomeRecordsTable** - `6d1f251` (feat)

## Files Created/Modified
- `database/migrations/2026_09_18_090000_create_external_invoice_references_table.php` - Schema: unique FK `income_record_id`, unique `invoice_number`, nullable `cufe`/`provider`/`document_url`
- `app/Models/ExternalInvoiceReference.php` - Model, no Auditable, `belongsTo(IncomeRecord::class)`
- `database/factories/ExternalInvoiceReferenceFactory.php` - Factory for tests
- `app/Models/IncomeRecord.php` - Added `externalInvoiceReference(): HasOne`
- `app/Filament/Resources/IncomeRecords/Tables/IncomeRecordsTable.php` - Added `->recordActions([...])` with the `external_invoice` Action
- `tests/Feature/ExternalInvoiceReferenceTest.php` - 7 Pest tests (3 model-level, 4 action-level)

## Decisions Made
- Followed the plan's exact interface spec (migration/model/factory/action code was prescribed verbatim in the plan's `<interfaces>` section) — no architectural deviation from what was planned.
- Verified via Boost's `search-docs`-equivalent code inspection (`vendor/filament/actions/src/Testing/TestsActions.php`) that `mountAction()` accepts a `TestAction::make(...)->table($record)` the same way `callAction()` does, confirming the plan's Test 7 approach (`assertSchemaStateSet` after `mountAction`) was directly usable without adaptation.

## Deviations from Plan

None - plan executed exactly as written. The migration/model/factory/action code matched the plan's prescribed snippets verbatim.

## Issues Encountered
- **Worktree desync (recurring, same pattern documented in every prior phase's STATE.md Blockers section):** this worktree's branch was still on an unrelated older commit ("Libro Mayor / bank reconciliation"), with no `.planning/`, `vendor/`, `.env`, `node_modules/`, or `public/build/`. Verified `git merge-base --is-ancestor HEAD main` (true, strict ancestor, no local work at risk) and fast-forwarded with `git merge main --ff-only` (non-destructive). Then ran full bootstrap: `composer install`, `cp .env.example .env && php artisan key:generate`, `npm install && npm run build`.
- **Docker/Postgres unavailable:** the shared dev Postgres (previously a Docker container, per prior sessions' notes) was not reachable this run — `docker ps` failed with "Cannot connect to the Docker daemon"; `open -a Docker` triggered what looked like a stuck installer process, not a working daemon. Did not block this plan: `phpunit.xml` forces `DB_CONNECTION=sqlite`/`:memory:` for the whole test suite (`LazilyRefreshDatabase` runs all migrations, including the new one, against sqlite on every test), which is what actually validates the migration's correctness for this plan's acceptance criteria. Did not attempt `php artisan migrate` against the real Postgres dev DB — no migration was run against it. Full Pest suite (233 tests, 799 assertions) passes clean on sqlite.
- **`npm install` mutated `package-lock.json`'s `name` field** to the worktree directory's basename (same known issue documented in Phase 3's summaries) — reverted with `git checkout -- package-lock.json` before staging any plan files; not committed.

## User Setup Required

None - no external service configuration required. This phase deliberately has zero external integration (INVHOOK-03).

## Next Phase Readiness
- Phase 4 (Fase E) complete: INVHOOK-01, INVHOOK-02, INVHOOK-03 all closed with test coverage.
- Phase 5 (Fase D, Exportación Excel) has no technical dependency on this phase and can start independently; still gated on user approval of an Excel export library per `.planning/PROJECT.md` constraints.
- **Unresolved environment blocker for future worktree sessions in this repo:** Docker Desktop did not come up in this session despite `open -a Docker` + ~60s wait (ps showed an install-in-progress process, not a running daemon). If a future phase needs the real Postgres dev DB (not just the sqlite test suite), this will need to be investigated/fixed first — not done here since it wasn't required for Phase 4's acceptance criteria.

---
*Phase: 04-hook-de-facturaci-n-externa-fase-e*
*Completed: 2026-09-18*

## Self-Check: PASSED

All 7 created/modified files confirmed present on disk; both task commits (`9c9258e`, `6d1f251`) confirmed in `git log`.

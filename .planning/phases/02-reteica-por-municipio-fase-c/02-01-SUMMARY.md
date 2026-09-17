---
phase: 02-reteica-por-municipio-fase-c
plan: 01
subsystem: database
tags: [divipola, dane, eloquent, seeder, enum, filament]

# Dependency graph
requires:
  - phase: 01-cotizacion-electronica-fase-a
    provides: established Filament v5 enum pattern (VoucherType) and committed-JSON-fixture ingestion pattern (ArchiveMasterPreviewImporter) reused here
provides:
  - Normalized departments/municipalities schema with full DIVIPOLA catalog (33 departments, 1122 municipalities) committed as a JSON fixture
  - Department/Municipality Eloquent models + factories for test usage
  - Idempotent DivipolaCatalogSeeder wired into DatabaseSeeder
  - WithholdingType enum (ReteFuente/ReteIVA/Ica) with HasColor/HasIcon/HasLabel
affects: [02-02-reteica-por-municipio-fase-c, 02-03-reteica-por-municipio-fase-c]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Committed-JSON-fixture ingestion for reference catalogs (no runtime network dependency), matching ArchiveMasterPreviewImporter's json_decode pattern"
    - "Dedicated idempotent Seeder (upsert-based) for large reference catalogs, kept out of DatabaseSeeder's inline logic and out of default test factories"

key-files:
  created:
    - database/migrations/2026_09_17_090000_create_departments_table.php
    - database/migrations/2026_09_17_090001_create_municipalities_table.php
    - app/Models/Department.php
    - app/Models/Municipality.php
    - database/factories/DepartmentFactory.php
    - database/factories/MunicipalityFactory.php
    - database/seeders/data/divipola.json
    - database/seeders/DivipolaCatalogSeeder.php
    - app/Enums/WithholdingType.php
    - tests/Feature/DivipolaCatalogTest.php
  modified:
    - database/seeders/DatabaseSeeder.php

key-decisions:
  - "municipalities.code is the 3-digit suffix of DIVIPOLA's 5-digit cod_mpio (not the full code), matching Company.dane_municipality_code's existing varchar(3) convention"
  - "Fixture generation script must explicitly cast department code to string before use as a PHP array key, otherwise PHP silently casts non-zero-padded numeric-string keys (e.g. '11', '13') to integers, corrupting the committed JSON's type consistency"

patterns-established:
  - "Reference catalogs (DIVIPOLA) are seeded via a dedicated Seeder class using DB::table()->upsert(), never inside a migration, and never implicitly required by feature tests (which use the factories instead)"

requirements-completed: [RETICA-01]

# Metrics
duration: ~30min
completed: 2026-09-17
---

# Phase 2 Plan 1: DIVIPOLA Catalog Foundation Summary

**Normalized departments/municipalities schema seeded from a committed DIVIPOLA fixture (33 departments, 1122 municipalities) plus the WithholdingType enum Plan 02 will consume.**

## Performance

- **Duration:** ~30 min (includes worktree catch-up and `composer install`, not just task execution)
- **Started:** 2026-09-16T23:37:00-05:00 (worktree fast-forward)
- **Completed:** 2026-09-16T23:55:23-05:00
- **Tasks:** 3 completed
- **Files modified:** 11 (10 created, 1 modified)

## Accomplishments
- Full DANE/DIVIPOLA catalog (33 departments, 1122 municipalities) fetched live from `datos.gov.co` (dataset `gdxc-w37w`), normalized, and committed as `database/seeders/data/divipola.json` — no runtime network dependency
- `departments`/`municipalities` tables with correct FK integrity and `(department_id, code)` uniqueness constraint
- Idempotent `DivipolaCatalogSeeder` (upsert-based) wired into `DatabaseSeeder`
- `WithholdingType` enum (`ReteFuente`/`ReteIVA`/`Ica`) ready for Plan 02's `WithholdingRule.concept` replacement, matching `02-UI-SPEC.md` badge assignments exactly
- `DivipolaCatalogTest` proves referential integrity, correct 5-digit→2+3-digit code splitting, and the enum's label/color/icon contract

## Task Commits

Each task was committed atomically:

1. **Task 1: Departments/municipalities schema, models, factories** - `4d83759` (feat)
2. **Task 2: DIVIPOLA fixture generation and seeder** - `fa3a40d` (feat)
3. **Task 3: WithholdingType enum + catalog integration test (TDD)** - `c29b04d` (test, RED) then `530d0cf` (feat, GREEN)

**Plan metadata:** pending (this commit)

## Files Created/Modified
- `database/migrations/2026_09_17_090000_create_departments_table.php` - `departments` table (2-digit unique code)
- `database/migrations/2026_09_17_090001_create_municipalities_table.php` - `municipalities` table (FK to department, 3-digit code, composite unique)
- `app/Models/Department.php` - `hasMany(Municipality::class)`
- `app/Models/Municipality.php` - `belongsTo(Department::class)`
- `database/factories/DepartmentFactory.php` / `database/factories/MunicipalityFactory.php` - test-only factories, independent of the full catalog
- `database/seeders/data/divipola.json` - committed fixture (33 departments, 1122 municipalities)
- `database/seeders/DivipolaCatalogSeeder.php` - idempotent `upsert()`-based loader
- `database/seeders/DatabaseSeeder.php` - calls `DivipolaCatalogSeeder` first
- `app/Enums/WithholdingType.php` - `ReteFuente`/`ReteIVA`/`Ica` with `HasColor`/`HasIcon`/`HasLabel`
- `tests/Feature/DivipolaCatalogTest.php` - 3 Pest tests (seeding integrity, code-split resolution, enum contract)

## Decisions Made
- `municipalities.code` stores only the 3-digit DIVIPOLA suffix (not the full 5-digit `cod_mpio`), so it matches `Company.dane_municipality_code`'s existing `varchar(3)` convention and lets Plan 03's default-resolution lookup use direct string comparison.
- Fixture generation explicitly casts department codes to `(string)` before using them as PHP array keys — PHP would otherwise silently cast non-zero-padded numeric-string keys (`"11"`, `"13"`, ...) to integers, which was caught and fixed during Task 2 (see Deviations).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] PHP array-key integer coercion corrupted department codes in the generated fixture**
- **Found during:** Task 2 (DIVIPOLA fixture generation)
- **Issue:** The plan's conversion script used `$departments[$deptCode] = $deptName` as a dedup map. PHP automatically casts array keys that look like non-zero-padded decimal integers (e.g. `"11"`, `"13"`, `"15"`) to actual `int` type, while zero-padded codes like `"05"` remain strings. This produced a fixture with mixed types (`"code": "05"` vs `"code": 11`), inconsistent with the plan's own example shape and a latent bug for any strict-type consumer.
- **Fix:** Cast `$deptCode` to `(string)` both when building the dedup map and when emitting each department's `code` field, guaranteeing all codes serialize as JSON strings.
- **Files modified:** `database/seeders/data/divipola.json` (generation-time only; no application code change)
- **Verification:** Re-ran a type-check pass over all 33 department entries confirming every `code` is a string; re-verified Medellín (`department_code: "05"`, `code: "001"`) resolves correctly.
- **Committed in:** `fa3a40d` (Task 2 commit)

**2. [Rule 3 - Blocking] Missing `.env`/`APP_KEY` and uninstalled Composer dependencies in this fresh worktree**
- **Found during:** Pre-Task 1 setup and full-suite verification
- **Issue:** This worktree had no `vendor/`, no `.env`, and its git branch was a stale ancestor of `main` missing `.planning/` entirely (see Issues Encountered below) — none of the plan's verification commands could run.
- **Fix:** Fast-forwarded the worktree branch to `main` (no divergent commits existed, so this was a pure fast-forward), ran `composer install`, copied `.env.example` to `.env`, and ran `php artisan key:generate`.
- **Files modified:** none tracked (`vendor/`, `.env` are gitignored)
- **Verification:** `php artisan test --compact` runs; `DivipolaCatalogTest` and Pint both pass.
- **Committed in:** not committed (environment-only, no tracked file changes)

---

**Total deviations:** 2 auto-fixed (1 bug, 1 blocking)
**Impact on plan:** Both fixes were necessary for correctness (fixture type consistency) and to execute at all (worktree environment). No scope creep — no plan tasks were altered in intent.

## Issues Encountered
- This worktree's git branch (`worktree-agent-aaf8bb8263bf54947`) was a strict ancestor of `main`, predating the entire `.planning/` directory and Phase 1 (Cotización) work — it had apparently never been updated since creation. Confirmed via `git merge-base` that no divergent commits existed, then fast-forwarded (`git merge main --ff-only`) to bring in `.planning/`, Phase 1 code, and the Phase 2 plan itself. This also resolves the previously-documented `gsd-tools.cjs` bug (noted in `STATE.md`) where state-writing commands fell back to the shared main checkout's `.planning/` because the worktree lacked a local `.planning/` directory — now that `.planning/` exists locally, `gsd-tools.cjs`'s cwd-resolution logic will use this worktree's own state files going forward.
- Full `php artisan test --compact` run surfaced 3 pre-existing, unrelated failures (`ExampleTest`/`WelcomePageTest` welcome-page tests) caused by a missing Vite build (`public/build/manifest.json` not present — `npm install`/`npm run build` never run in this worktree). Out of scope for this backend-only plan; logged in `.planning/phases/02-reteica-por-municipio-fase-c/deferred-items.md` for whichever plan next touches the frontend.

## User Setup Required
None - no external service configuration required. (Local `.env`/`APP_KEY` were generated automatically as part of worktree setup, not a manual step.)

## Next Phase Readiness
- Plan 02 can now extend `WithholdingRule` with `WithholdingType` (replacing `concept`) and add `municipality_id`, using the `Department`/`Municipality` models and full seeded catalog from this plan.
- Plan 03 can resolve a `Company`'s default municipality via `dane_department_code`/`dane_municipality_code` matching `Department.code`/`Municipality.code` directly (string comparison, no transformation needed).
- Before any frontend-touching plan in this worktree, run `npm install && npm run build` (see deferred-items.md).

---
*Phase: 02-reteica-por-municipio-fase-c*
*Completed: 2026-09-17*

## Self-Check: PASSED

All 12 created/modified files found on disk; all 4 task commits (`4d83759`, `fa3a40d`, `c29b04d`, `530d0cf`) confirmed in `git log`.

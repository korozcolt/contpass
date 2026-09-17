---
phase: 01-cotizaci-n-electr-nica-fase-a
plan: 3
subsystem: filament-ui
tags: [filament, livewire-testing, lifecycle-actions, quotation]

# Dependency graph
requires:
  - phase: 01-cotizaci-n-electr-nica-fase-a plan 1
    provides: "Quotation/QuotationLine models, QuotationStatus enum, composite unique (company_id, number) index"
  - phase: 01-cotizaci-n-electr-nica-fase-a plan 2
    provides: "BuildQuotationNumber::next(), ConvertQuotationToIncome::handle()"
provides:
  - "App\\Filament\\Resources\\Quotations\\QuotationResource — full CRUD resource, navigationGroup Operación"
  - "Quotation lifecycle row actions (send/accept/reject/convert) wired to the domain services"
affects: [01-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "canEdit(Model $record) on the Resource delegates to a model method (isReadOnly()), and the Edit page independently re-checks it in mount() via abort_if() — same immutability pattern as Voucher::ensureEditable(), applied to Filament defense in depth"
    - "Table row actions with a dedicated ->form([...]) modal for the one field that needs required() (rejection_reason), instead of a conditional field on the main form — avoids the known Filament v5 $get()-vs-enum bug by construction, not by comparison discipline"
    - "handleRecordCreation() override on CreateRecord catches a driver-agnostic Illuminate\\Database\\UniqueConstraintViolationException and re-throws as ValidationException with a user-facing Spanish message, closing the loop on a documented DB-level race condition"

key-files:
  created:
    - app/Filament/Resources/Quotations/QuotationResource.php
    - app/Filament/Resources/Quotations/Schemas/QuotationForm.php
    - app/Filament/Resources/Quotations/Schemas/QuotationInfolist.php
    - app/Filament/Resources/Quotations/Pages/ListQuotations.php
    - app/Filament/Resources/Quotations/Pages/CreateQuotation.php
    - app/Filament/Resources/Quotations/Pages/EditQuotation.php
    - app/Filament/Resources/Quotations/Pages/ViewQuotation.php
    - app/Filament/Resources/Quotations/Tables/QuotationsTable.php
    - tests/Feature/QuotationLifecycleTest.php
  modified: []

key-decisions:
  - "Rejection reason assertion in the Livewire test uses assertHasFormErrors() (not the generic assertHasErrors()) for the table-action modal form, because Filament prefixes error keys with the mounted action's schema state path (e.g. mountedActionSchema0.rejection_reason) — assertHasFormErrors() resolves that prefix automatically; assertHasErrors() worked as-is for the top-level CreateQuotation form, which has no schema state path"
  - "Renamed the test's shared fixture helper to quotationLifecycleFixture() (plan snippet used quotationFixture()) because that function name was already declared globally by tests/Feature/QuotationConversionTest.php (Plan 2) — Pest loads all Feature test files into the same global namespace"

patterns-established:
  - "Pattern: one-click lifecycle transitions on a Filament table use forceFill()+save() directly in the Action closure when there is no domain invariant to protect (send/accept/reject are pure status changes); only the money-moving transition (convert) delegates to a domain service"

requirements-completed: [QUOT-01, QUOT-03, QUOT-05, QUOT-06, QUOT-07]

# Metrics
duration: ~50min
completed: 2026-09-16
---

# Phase 01 Plan 3: Quotation Filament resource and lifecycle UI Summary

**Full `QuotationResource` (form + read-only infolist + 4 pages + lifecycle table actions) wired directly to `BuildQuotationNumber` and `ConvertQuotationToIncome` from Plan 2, with an end-to-end Livewire test proving the complete Draft→Sent→Accepted→Converted flow, the required-rejection-reason guard, the 403 on editing a converted quotation, and the friendly UI-SPEC message on a numbering collision.**

## Performance

- **Tasks:** 3 completed
- **Files created:** 9

## Accomplishments
- `QuotationResource` registers under navigationGroup "Operación" with `canEdit()` blocking converted quotations at the resource level
- `QuotationForm` never exposes `number` or `status` as editable fields — both are set exclusively by the numbering service and by table actions, respectively
- `QuotationInfolist` shows a working link to the generated `Voucher` once converted (`VoucherResource::getUrl('view', ...)`)
- `CreateQuotation::mutateFormDataBeforeCreate()` calls `BuildQuotationNumber::next()` so the user never types a number; `handleRecordCreation()` catches `UniqueConstraintViolationException` from the composite unique index and re-throws as a `ValidationException` with the exact UI-SPEC copy
- `EditQuotation::mount()` independently `abort_if(403)`s on a read-only (converted) quotation — defense in depth beyond the hidden Edit button
- `QuotationsTable` implements all four lifecycle row actions (Enviar/Aceptar/Rechazar/Convertir a ingreso) with UI-SPEC colors/icons; Rechazar's `rejection_reason` lives in a dedicated modal form, sidestepping the known Filament v5 `$get()`-vs-enum comparison bug by construction
- `QuotationLifecycleTest` (3 cases, all passing): full lifecycle with exactly one `Voucher` created and a verified `IncomeRecord` amount; rejection without a reason blocked; number-collision at insert time shows the friendly message

## Task Commits

Each task was committed atomically:

1. **Task 1: QuotationResource + QuotationForm + QuotationInfolist** — `5413be3` (feat)
2. **Task 2: List/Create/Edit/View pages** — `11041d6` (feat)
3. **Task 3: QuotationsTable + QuotationLifecycleTest** — `7b96d41` (feat)

**Plan metadata:** committed together with this SUMMARY (see final commit)

## Files Created/Modified
- `app/Filament/Resources/Quotations/QuotationResource.php` - Resource skeleton, `canEdit()` guard, page registration
- `app/Filament/Resources/Quotations/Schemas/QuotationForm.php` - third party, revenue/receivable accounts (class 4/13), line repeater (min 1), optional notes
- `app/Filament/Resources/Quotations/Schemas/QuotationInfolist.php` - read-only view, `Voucher` link
- `app/Filament/Resources/Quotations/Pages/{List,Create,Edit,View}Quotation.php` - numbering, read-only guard, collision handling
- `app/Filament/Resources/Quotations/Tables/QuotationsTable.php` - columns, status filter, 4 lifecycle actions
- `tests/Feature/QuotationLifecycleTest.php` - end-to-end Livewire coverage

## Decisions Made
- Used `assertHasFormErrors()` instead of the plan snippet's `assertHasErrors()` for the reject-action test, because Filament's table-action modal forms nest error keys under the mounted action's schema state path; `assertHasFormErrors()` resolves that prefix, `assertHasErrors()` does not. Confirmed by reading `vendor/filament/forms/src/Testing/TestsForms.php` directly (`search-docs` MCP tool was not available in this execution environment, so I verified against the installed package source instead, per the plan's own fallback intent of "confirm the exact method before finalizing").
- Renamed the local fixture helper `quotationFixture()` → `quotationLifecycleFixture()` to avoid a PHP fatal "cannot redeclare function" collision with the identically-named global helper already declared in `tests/Feature/QuotationConversionTest.php` (Plan 2).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Worktree was 28 commits behind `main` and missing `.planning/`, `vendor/`, `.env`, built frontend assets**
- **Found during:** environment setup, before Task 1
- **Issue:** This worktree's branch pointed at an old commit (39cc548) while local `main` was 28 commits ahead (f9c61e8), including all of Plans 1 and 2's work this plan depends on (`Quotation`/`QuotationLine` models, `BuildQuotationNumber`, `ConvertQuotationToIncome`). No `vendor/`, `.env`, or compiled `public/build/` assets existed either.
- **Fix:** `git merge --ff-only main` (clean fast-forward, 0 divergent commits), `composer install`, generated `.env` (sqlite-backed, app key, session/cache/queue set to `database` driver — matching `phpunit.xml`'s test environment), `php artisan migrate`, `npm install && npm run build`.
- **Files modified:** none tracked by this plan (environment setup only)
- **Verification:** baseline `php artisan test --compact` after setup: 173/173 passing (previous plans' SUMMARYs recorded 3 pre-existing Vite-manifest failures; building frontend assets this time resolved all 3, so this plan's baseline and final state are both fully green with zero pre-existing or new failures)
- **Committed in:** not applicable (no file changes; branch fast-forward only)

**2. [Rule 1 - Bug] `SESSION_CONNECTION=default` in generated `.env` broke every artisan command needing the database**
- **Found during:** environment setup, verifying `php artisan route:list --name=quotations`
- **Issue:** Laravel's database session driver passed the literal string `"default"` to `DB::connection('default')`, but no connection is configured under that name (only `sqlite`/`mysql`/`pgsql`/`sqlsrv` keys exist) — `InvalidArgumentException: Database connection [default] not configured.` on every command.
- **Fix:** Set `SESSION_CONNECTION=null` in `.env` so Laravel falls back to the actual default database connection.
- **Files modified:** `.env` (gitignored, not committed)
- **Verification:** `php artisan route:list --name=quotations` succeeded afterward, listing all 4 registered routes

---

**Total deviations:** 2 auto-fixed (1 blocking environment setup, 1 blocking bug in generated local config)
**Impact on plan:** Both deviations were environment-only; no plan behavior, file, or test scope changed.

## Issues Encountered
None beyond the deviations above. Full suite: 173/173 passing, `vendor/bin/pint --dirty --format agent` clean.

## User Setup Required

None - no external service configuration required for this plan's code. (Note: this worktree's local `.env`/`vendor/`/built assets are gitignored environment scaffolding, not committed; a fresh checkout still needs the standard `composer install && npm install && npm run build && php artisan migrate` steps documented in `CLAUDE.md`.)

## Next Phase Readiness
- `QuotationResource` is fully navigable at `/admin/quotations` with create/list/view/edit, ready for Plan 4 (PDF generation, QUOT-04) to add a "Descargar PDF" action against the same `Quotation` model without touching this plan's files.
- No blockers.

---
*Phase: 01-cotizaci-n-electr-nica-fase-a*
*Completed: 2026-09-16*

## Self-Check: PASSED

All 9 created files verified present on disk. All 3 task commits (`5413be3`, `11041d6`, `7b96d41`) verified present in `git log`.

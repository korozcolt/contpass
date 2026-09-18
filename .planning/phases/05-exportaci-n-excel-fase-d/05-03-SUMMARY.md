---
phase: 05-exportaci-n-excel-fase-d
plan: 03
subsystem: api
tags: [laravel, filament, openspout, xlsx-export, accounting-reports]

# Dependency graph
requires:
  - phase: 05-exportaci-n-excel-fase-d (Plan 05-01)
    provides: ExcelReportExporter service (typed .xlsx StreamedResponse from headers+rows)
  - phase: 05-exportaci-n-excel-fase-d (Plan 05-02)
    provides: 6 private *Rows() row-builder methods on AccountingReportController, shared between CSV and Excel export paths
provides:
  - 6 public *Xlsx() controller methods (ledgerXlsx, trialBalanceXlsx, thirdPartyMovementsXlsx, generalLedgerXlsx, accountsReceivableXlsx, accountsPayableXlsx)
  - 6 new authenticated GET routes (accounting-reports/{report}.xlsx) with the same abort_unless(403) gate as the CSV routes
  - End-to-end Pest coverage proving native typed cells (date, float) in the downloaded ledger .xlsx file
affects: [05-exportaci-n-excel-fase-d (Plan 05-04, wires Filament "Exportar Excel" buttons to these routes)]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "HTTP-level .xlsx export route: closure-wrapped Route::get with abort_unless(403) gate calling a controller *Xlsx() method, identical shape to the existing CSV routes — just a '.xlsx' suffix on both the URI and method name"
    - "*Xlsx() controller methods are thin: app(ExcelReportExporter::class)->handle(filename, headers, $this->{report}Rows(...)) — zero export-format-specific logic in the controller, all typing lives in ExcelReportExporter (05-01)"

key-files:
  created:
    - tests/Feature/AccountingReportXlsxExportTest.php
  modified:
    - app/Http/Controllers/AccountingReportController.php
    - routes/web.php
    - .planning/phases/05-exportaci-n-excel-fase-d/deferred-items.md

key-decisions:
  - "No deviations from plan — implemented exactly as specified, including the note that accountsReceivableXlsx()/accountsPayableXlsx() take no arguments (matching their CSV counterparts) even though their route closures still receive Request $request for the abort_unless gate"

patterns-established:
  - "Pattern: .xlsx routes are always added immediately after their CSV sibling route in routes/web.php, keeping paired CSV/.xlsx routes visually grouped"

requirements-completed: [XLSEXPORT-01, XLSEXPORT-02, XLSEXPORT-03]

# Metrics
duration: ~20min
completed: 2026-09-18
---

# Phase 05 Plan 03: Rutas .xlsx autenticadas para los 6 reportes contables Summary

**6 rutas `.xlsx` autenticadas (`GET accounting-reports/{report}.xlsx`) que reusan los row-builders de 05-02 y el `ExcelReportExporter` de 05-01, con prueba end-to-end de tipado nativo de celdas (fecha/débito) en el libro auxiliar.**

## Performance

- **Duration:** ~20 min
- **Tasks:** 2 completed
- **Files modified:** 3 (1 created, 2 modified) + deferred-items.md note

## Accomplishments
- 6 nuevos métodos públicos `*Xlsx()` en `AccountingReportController`, cada uno una llamada de una línea a `ExcelReportExporter::handle()` reusando los métodos privados `*Rows()` de 05-02
- 6 nuevas rutas `.xlsx` en `routes/web.php`, con el mismo gate `abort_unless($request->user() !== null, 403)` que las 9 rutas CSV existentes — ahora 15 rutas totales bajo `accounting-reports/*`
- Prueba end-to-end que abre el archivo `.xlsx` real descargado (no solo el servicio aislado) y confirma que la celda de fecha es `DateTimeInterface` y la celda de débito es `float`/`int` nativo, no texto

## Task Commits

Each task was committed atomically:

1. **Task 1: Write failing Pest test for the 6 .xlsx routes** - `b1827e3` (test)
2. **Task 2: Add the 6 *Xlsx() controller methods and .xlsx routes** - `61a9e62` (feat)

**Plan metadata:** (pending) `docs(05-03): complete plan`

## Files Created/Modified
- `tests/Feature/AccountingReportXlsxExportTest.php` - 3 test groups: guest 403 gate (dataset x6), authenticated download + content-type (dataset x6), ledger native cell typing (date + debit)
- `app/Http/Controllers/AccountingReportController.php` - Added `use App\Services\Reports\ExcelReportExporter;` and 6 public `*Xlsx()` methods placed directly after their CSV counterparts
- `routes/web.php` - Added 6 `.xlsx` routes immediately after their corresponding CSV route

## Decisions Made
None - plan executed exactly as written.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

**Worktree environment bootstrap (expected, per documented pattern):** This worktree was not synced with `main` at start (5+ commits behind, missing `.planning/`, `vendor/`, `.env`, `node_modules/`, `public/build/`). Fast-forwarded to `main` (`git merge main --ff-only`, verified ancestor first) and ran `composer install` + `.env`/`key:generate`. Deliberately did **not** run `npm install && npm run build`, since this plan is backend-only (controller + routes + Pest test, no frontend surface) — same precedent as 05-01 and 05-02. Full suite reproduces the same 3 pre-existing `ViteManifestNotFoundException` failures (welcome page / `@vite` directive) documented in `deferred-items.md`; confirmed unrelated to this plan's changes (254 tests, 251 passed, 3 failed — all three pre-existing). The 13 new tests in `AccountingReportXlsxExportTest` all pass.

Per the `known_worktree_issue` in this execution's prompt, no `gsd-tools.cjs` state-writing commands were invoked. `STATE.md`, `ROADMAP.md`, and `REQUIREMENTS.md` in this worktree were updated directly with Edit/Write.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

Plan 05-04 (wiring Filament "Exportar Excel" buttons) can now link directly to these 6 named routes (`route('accounting-reports.ledger.xlsx')`, etc.) via `->url()->openUrlInNewTab()`, following the same pattern already used for the existing CSV export table actions. No blockers.

---
*Phase: 05-exportaci-n-excel-fase-d*
*Completed: 2026-09-18*

## Self-Check: PASSED

- FOUND: tests/Feature/AccountingReportXlsxExportTest.php
- FOUND: app/Http/Controllers/AccountingReportController.php
- FOUND: routes/web.php
- FOUND: .planning/phases/05-exportaci-n-excel-fase-d/05-03-SUMMARY.md
- FOUND commit: b1827e3
- FOUND commit: 61a9e62

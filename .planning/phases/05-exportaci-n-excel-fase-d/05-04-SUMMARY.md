---
phase: 05-exportaci-n-excel-fase-d
plan: 04
subsystem: ui
tags: [filament, livewire, pest, excel-export]

# Dependency graph
requires:
  - phase: 05-exportaci-n-excel-fase-d (Plan 05-03)
    provides: 6 rutas .xlsx autenticadas (accounting-reports.*.xlsx) reusando ExcelReportExporter + row-builders privados
provides:
  - Botón "Exportar Excel" en las 6 páginas Filament de reporte, hermano del botón "Exportar CSV" ya existente
  - Cobertura Pest de que cada una de las 6 páginas expone la acción exportExcel con la URL .xlsx correcta
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Segunda Action::make('exportExcel') dentro del mismo array headerActions([...]), inmediatamente después de Action::make('export') existente, sin tocar la acción CSV"

key-files:
  created:
    - tests/Feature/AccountingReportExcelActionsTest.php
  modified:
    - app/Filament/Pages/LedgerReport.php
    - app/Filament/Pages/ThirdPartyMovementsReport.php
    - app/Filament/Pages/TrialBalanceReport.php
    - app/Filament/Pages/GeneralLedgerReport.php
    - app/Filament/Pages/AccountsReceivableReport.php
    - app/Filament/Pages/AccountsPayableReport.php

key-decisions:
  - "Los 6 headerActions() existentes coincidían exactamente con lo documentado en 05-CONTEXT.md/interfaces del plan — sin desviaciones de forma, solo se añadió la segunda Action::make('exportExcel') tal cual estaba especificada"
  - "AccountsPayableReport requiere Company::factory()->create(['has_budgetary_control' => true]) antes de montar el test (canAccess() gate), las otras 5 páginas usan el Company auto-creado por defecto de CurrentCompany::get()"

patterns-established: []

requirements-completed: [XLSEXPORT-01]

# Metrics
duration: ~20min
completed: 2026-09-18
---

# Phase 5 Plan 4: Botón "Exportar Excel" en reportes Filament Summary

**Segunda Action::make('exportExcel') añadida a las 6 páginas Filament de reporte, hermana del botón "Exportar CSV" ya existente, apuntando a las 6 rutas `.xlsx` de Plan 05-03 con el mismo comportamiento de filtros que su acción CSV correspondiente.**

## Performance

- **Duration:** ~20 min (incluye bootstrap completo del worktree)
- **Started:** 2026-09-18T17:00Z (aprox.)
- **Completed:** 2026-09-18T17:05Z
- **Tasks:** 3
- **Files modified:** 7 (6 páginas Filament + 1 test nuevo)

## Accomplishments
- Las 4 páginas de reporte "filtradas" (LedgerReport, ThirdPartyMovementsReport, TrialBalanceReport, GeneralLedgerReport) muestran ambos botones "Exportar CSV"/"Exportar Excel", el segundo propagando `$this->reportQueryParameters()` igual que el CSV
- Las 2 páginas "no filtradas" (AccountsReceivableReport, AccountsPayableReport) muestran ambos botones sin parámetros de filtro, preservando la asimetría ya existente de su acción CSV (research Pitfall 4)
- Test Pest nuevo (`AccountingReportExcelActionsTest`, 6 casos) prueba con `assertTableActionExists()`/`assertTableActionHasUrl()` que cada una de las 6 páginas expone `exportExcel` resolviendo a su ruta `.xlsx` correcta
- XLSEXPORT-01/02/03 cierran end-to-end desde la perspectiva del usuario final (antes de este plan, las rutas `.xlsx` de 05-03 solo eran alcanzables tecleando la URL a mano)

## Task Commits

Each task was committed atomically:

1. **Task 1: Add "Exportar Excel" action to the 4 filtered report pages** - `99d4f17` (feat)
2. **Task 2: Add "Exportar Excel" action to the 2 unfiltered report pages** - `420206b` (feat)
3. **Task 3: Write Pest test verifying all 6 pages expose the exportExcel action** - `9dc7f27` (test)

_Note: no refactor commit needed — pint passed clean on first run for all 6 files._

## Files Created/Modified
- `app/Filament/Pages/LedgerReport.php` - exportExcel Action -> `accounting-reports.ledger.xlsx`
- `app/Filament/Pages/ThirdPartyMovementsReport.php` - exportExcel Action -> `accounting-reports.third-party-movements.xlsx`
- `app/Filament/Pages/TrialBalanceReport.php` - exportExcel Action -> `accounting-reports.trial-balance.xlsx`
- `app/Filament/Pages/GeneralLedgerReport.php` - exportExcel Action -> `accounting-reports.general-ledger.xlsx`
- `app/Filament/Pages/AccountsReceivableReport.php` - exportExcel Action -> `accounting-reports.accounts-receivable.xlsx` (sin parámetros)
- `app/Filament/Pages/AccountsPayableReport.php` - exportExcel Action -> `accounting-reports.accounts-payable.xlsx` (sin parámetros, requiere `has_budgetary_control`)
- `tests/Feature/AccountingReportExcelActionsTest.php` - 6 tests, uno por página, verificando existencia de la acción y su URL resuelta

## Decisions Made
- Ninguna decisión nueva de arquitectura — el plan traía la forma exacta del código (interfaces + read_first) y coincidía 1:1 con el estado real de los 6 archivos leídos antes de editar.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

Fase D (Exportación Excel) queda completa (4/4 plans). Con esto, el milestone "Mejoras Comerciales para Mercado Privado" cierra sus 24 requirements v1 (QUOT, RETICA, BANKREC, INVHOOK, XLSEXPORT). No hay fases pendientes en el roadmap activo — siguiente paso natural es `/gsd:complete-milestone` o definir un nuevo milestone.

**Bootstrap del worktree (contexto para futuras ejecuciones):** a diferencia de plans anteriores de esta fase (05-01/05-03) que omitieron deliberadamente `npm install && npm run build` por ser backend-only, este plan sí tocaba páginas Filament (superficie de build frontend) y corrió el bootstrap completo. Efecto colateral positivo: los 3 fallos `ViteManifestNotFoundException` documentados desde 05-01 en `deferred-items.md` ya no se reproducen con `public/build/manifest.json` presente — full suite: 260/260 passed, 0 failed.

---
*Phase: 05-exportaci-n-excel-fase-d*
*Completed: 2026-09-18*

## Self-Check: PASSED

All 8 files (6 modified pages, 1 new test, 1 summary) confirmed present. All 3 task commits (99d4f17, 420206b, 9dc7f27) confirmed in git log.

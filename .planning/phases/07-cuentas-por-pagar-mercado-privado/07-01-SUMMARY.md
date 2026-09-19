---
phase: 07-cuentas-por-pagar-mercado-privado
plan: 01
subsystem: accounting-reports
tags: [accounts-payable, expense-record, private-market, gap-closure]

requires:
  - app/Services/Accounting/AccountsReceivable.php (patrón source_voucher_id)
  - app/Services/Accounting/PostExpenseVoucher.php (origen de ExpenseRecord con budget_obligation_id nulo)
provides:
  - "AccountsPayable::openItems() combinado (BudgetObligation público + ExpenseRecord privado)"
affects:
  - app/Http/Controllers/AccountingReportController.php (consumidor sin cambios, filas nuevas automáticamente)
  - app/Filament/Pages/AccountsPayableReport.php (consumidor sin cambios)

tech-stack:
  added: []
  patterns:
    - "Collection::concat() de dos sub-colecciones privadas (budgetObligationItems + privateMarketItems) filtradas una sola vez al final, en vez de duplicar el filtro `pending > 0.01` por sub-fuente"

key-files:
  created: []
  modified:
    - app/Services/Accounting/AccountsPayable.php
    - tests/Feature/AccountsPayableReportTest.php

decisions:
  - "D-01/D-02/D-03 de 07-CONTEXT.md aplicados sin desviación: alcance mínimo (source_voucher_id, sin tocar payment_order_id/Issue #3), columna 'número' = voucher.number, valor reportado neto de retención"

metrics:
  duration: ~25min
  completed: 2026-09-19
---

# Phase 7 Plan 1: Cuentas por pagar — alcance mercado privado Summary

AccountsPayable::openItems() ahora combina obligaciones presupuestales públicas con ExpenseRecords de mercado privado (budget_obligation_id nulo), usando el patrón source_voucher_id ya validado por AccountsReceivable, cerrando AP-01.

## What Was Built

- `app/Services/Accounting/AccountsPayable.php` reescrito: la lógica pública de `BudgetObligation`/`payment_order_id` se extrajo intacta a `budgetObligationItems()` (mismo cuerpo, sin cambios de comportamiento), y se agregó `privateMarketItems()` que consulta `ExpenseRecord::whereNull('budget_obligation_id')` con el mismo patrón `source_voucher_id` contra `Payment` que usa `AccountsReceivable::openItems()`. `openItems()` ahora es `budgetObligationItems($company)->concat(privateMarketItems($company))->filter(pending > 0.01)->values()`. Un único `bucket()` privado se reusa por ambas sub-fuentes (sin duplicación).
- Filas privadas devuelven `budget_obligation_id: null`, `number` = `expenseRecord.voucher.number` (D-02), y `amount`/`pending` = `expenseRecord.amount - expenseRecord.withholding_amount` (D-03, neto de retención), en vez del bruto que usan las filas públicas.
- 3 tests Pest nuevos en `tests/Feature/AccountsPayableReportTest.php`: inclusión con retención ICA/ReteFuente aplicada vía `PostExpenseVoucher`, exclusión al pago total vía `RegisterPayment(..., $sourceVoucher)`, y combinación de una fila pública + una privada para la misma `Company` en una sola llamada a `openItems()`. Los 5 tests existentes del camino público y los 3 de página/CSV pasan sin modificar (regresión cero).
- Ningún consumidor (`AccountingReportController::accountsPayableRows()`, CSV, Excel, página Filament `AccountsPayableReport`) requirió cambios — todos siguen leyendo el mismo shape de array de `openItems()`.

## Deviations from Plan

None - plan executed exactly as written. El código de `AccountsPayable.php` y los 3 tests nuevos coinciden con el `<action>` del plan al carácter.

### Entorno de ejecución (no es una desviación del plan, es housekeeping del worktree)

Este worktree (`agent-a7e1e317c17aec75d`) fue spawneado 5 commits detrás de `main`, sin `.planning/`, `vendor/`, `.env`, `node_modules/` ni `public/build/` — mismo patrón documentado sin excepción en las 12 ejecuciones anteriores de este proyecto (ver `.planning/STATE.md` Blockers/Concerns). Se verificó `git merge-base --is-ancestor HEAD main` (true, ancestro estricto) y se corrigió con `git merge main --ff-only` (no destructivo), seguido de bootstrap completo: `composer install`, `.env`+`key:generate`, `npm install && npm run build` (se corrió el build completo porque el plan requería confirmar full-suite verde sin los 3 fallos preexistentes `ViteManifestNotFoundException`). `npm install` volvió a mutar el campo `name` de `package-lock.json` — revertido con `git checkout -- package-lock.json` antes de correr `npm run build`. Tests corren 100% contra sqlite in-memory (`phpunit.xml`), sin necesidad de Postgres.

Dado el bug conocido de `gsd-tools.cjs`'s `findProjectRoot()` (documentado en `.planning/STATE.md` desde Phase 01 — redirige escrituras de estado al checkout principal compartido en vez del `.planning/` local de este worktree), ningún comando de estado de `gsd-tools.cjs` fue invocado en esta ejecución. `STATE.md`, `ROADMAP.md` y `REQUIREMENTS.md` se editan a mano directamente en este worktree, que es la fuente de verdad correcta.

## Verification

- `php artisan test --compact --filter=AccountsPayableReportTest` → 11/11 passed (5 regresión pública + 3 página/CSV + 3 nuevos de mercado privado).
- `php artisan test --compact` (suite completa) → 263/263 passed, 886 assertions, 0 failed (260 baseline de Phase 06 + 3 nuevos).
- `vendor/bin/pint --dirty --format agent` → limpio.
- Greps de aceptación: `private-market` ×3, `whereNull('budget_obligation_id')` ×1, `source_voucher_id` ×5, `private function bucket` ×1 (no duplicado), `payment_order_id` ×5 (intacto, camino público sin tocar).

## Self-Check: PASSED

- FOUND: app/Services/Accounting/AccountsPayable.php
- FOUND: tests/Feature/AccountsPayableReportTest.php
- FOUND commit: 0f1891d

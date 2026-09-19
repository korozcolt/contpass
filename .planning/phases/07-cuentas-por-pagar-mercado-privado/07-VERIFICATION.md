---
phase: 07-cuentas-por-pagar-mercado-privado
verified: 2026-09-19T00:00:00Z
status: passed
score: 6/6 must-haves verified
---

# Phase 7: Cuentas por pagar — alcance mercado privado Verification Report

**Phase Goal:** El reporte y export "cuentas por pagar" refleja también los ExpenseRecord del flujo de mercado privado (con budget_obligation_id nulo), no solo obligaciones presupuestales públicas — incluyendo los ICA-retenidos (Fase 2) y conciliados (Fase 3).
**Verified:** 2026-09-19
**Status:** passed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
| - | ----- | ------ | -------- |
| 1 | `AccountsPayable::openItems()` incluye ExpenseRecords de mercado privado (`budget_obligation_id` nulo), no solo `BudgetObligation` | ✓ VERIFIED | `openItems()` = `budgetObligationItems($company)->concat($this->privateMarketItems($company))->filter(...)->values()`; `privateMarketItems()` queries `ExpenseRecord::whereNull('budget_obligation_id')` |
| 2 | Fila de mercado privado muestra `expenseRecord.voucher.number` en la clave `number` (D-02) | ✓ VERIFIED | `'number' => $expense->voucher->number` in `privateMarketItems()`; asserted by test `includes a private-market expense record...` (`$rows->first()['number']` === `$voucher->number`) |
| 3 | Fila de mercado privado reporta valor neto de retención (D-03) | ✓ VERIFIED | `$netAmount = round((float) $expense->amount - (float) $expense->withholding_amount, 2)`; test asserts `192000.0` for `amount=200000`, `withholding=8000` |
| 4 | ExpenseRecord de mercado privado totalmente pagado (via `Payment.source_voucher_id`) queda excluido | ✓ VERIFIED | `privateMarketItems()` computes `paid` via `Payment::whereIn('source_voucher_id', ...)`; test `excludes a fully paid private-market expense record` passes (0 rows) |
| 5 | Camino público (`BudgetObligation`/`payment_order_id`, monto bruto) sin regresión | ✓ VERIFIED | `budgetObligationItems()` body byte-identical to pre-phase `openItems()` body (only extraction, no logic change); `payment_order_id` grep count = 5 (unchanged); all 5 pre-existing public-path tests pass unmodified |
| 6 | Pantalla, CSV y Excel muestran las mismas filas combinadas sin lógica duplicada | ✓ VERIFIED | `AccountingReportController::accountsPayableRows()` (line 327) and `AccountsPayableReport` Filament page untouched since commit 924201c (predates this phase); single commit 0f1891d only modifies `AccountsPayable.php` + test file |

**Score:** 6/6 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
| -------- | -------- | ------ | ------- |
| `app/Services/Accounting/AccountsPayable.php` | `openItems()` combines both sources, same public signature, same output shape | ✓ VERIFIED | Contains `budget_obligation_id`, `whereNull('budget_obligation_id')` ×1, `source_voucher_id` ×2 (whereIn + groupBy/pluck reference), single `private function bucket` reused by both sub-methods |
| `tests/Feature/AccountsPayableReportTest.php` | Pest coverage for inclusion, net amount, exclusion, combined rows, plus public-path regression | ✓ VERIFIED | Contains `private-market` ×3 (`includes a private-market...`, `excludes a fully paid private-market...`, `combines budget obligation and private-market...`); 5 pre-existing tests unmodified; 11/11 tests pass |

### Key Link Verification

| From | To | Via | Status | Details |
| ---- | -- | --- | ------ | ------- |
| `AccountsPayable.php` | `Payment.php` | `Payment::query()->whereIn('source_voucher_id', ...)` grouped by `source_voucher_id` | ✓ WIRED | Present in `privateMarketItems()`, mirrors `AccountsReceivable::openItems()` pattern exactly |
| `AccountsPayable.php` | `ExpenseRecord.php` | `ExpenseRecord::query()->whereNull('budget_obligation_id')->whereHas('voucher', ...)` | ✓ WIRED | Present verbatim in `privateMarketItems()` |
| `AccountingReportController.php` | `AccountsPayable.php` | `accountsPayableRows()` calls `openItems()` unchanged | ✓ WIRED | `grep -n "AccountsPayable::class)->openItems"` at line 327; controller not modified in this phase's commit (0f1891d touches only 2 files) |

### Data-Flow Trace (Level 4)

Not applicable in the standard sense (no React/Vue component) — this is a PHP domain service. Data-flow was traced instead via commit isolation: commit `0f1891d` (this phase's sole commit) modifies exactly `app/Services/Accounting/AccountsPayable.php` and `tests/Feature/AccountsPayableReportTest.php`. The consuming chain (`AccountingReportController::accountsPayableRows()` → CSV/Excel/Filament page) was last touched in commit `61a9e62`/`420206b` (Phase 5, before this phase) and is unmodified, confirming new private-market rows flow through to all three consumers without any additional wiring changes required — exactly as CONTEXT.md's Integration Points specified.

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| -------- | ------- | ------ | ------ |
| Filtered test suite for this phase's file | `php artisan test --compact --filter=AccountsPayableReportTest` | `{"result":"passed","tests":11,"passed":11,"assertions":25}` | ✓ PASS |
| Full test suite (regression) | `php artisan test --compact` | `{"result":"passed","tests":263,"passed":263,"assertions":886}` | ✓ PASS (matches expected 263, up from baseline 260 + 3 new) |
| Code style on touched files | `vendor/bin/pint --dirty --format agent` | `{"result":"passed"}` | ✓ PASS |
| Public-path logic byte-identical | `grep -c "payment_order_id" app/Services/Accounting/AccountsPayable.php` | `5` | ✓ PASS (unchanged reference count per plan acceptance criteria) |

Note: `vendor/bin/pint --test --format agent` (full-repo, non-dirty) reports 4 pre-existing style issues in unrelated files (`BudgetRevenueFactory.php`, `AccountingReportXlsxExportTest.php`, `ExcelReportExporterTest.php`, `BudgetRevenueTest.php`) — none touched by this phase, none introduced by it, consistent with the plan's `--dirty` scoping.

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| ----------- | ---------- | ----------- | ------ | -------- |
| AP-01 | 07-01-PLAN.md | El reporte/export "cuentas por pagar" incluye ExpenseRecords de mercado privado sin BudgetObligation asociada | ✓ SATISFIED | REQUIREMENTS.md line 52 marked `[x]`; Traceability table line 120 marks "Complete"; code verified above |

No orphaned requirements found for Phase 7 in REQUIREMENTS.md.

**Milestone gap-closure status:** With AP-01 confirmed complete, both gap-closure requirements from the v1.0 audit are now done — REQUIREMENTS.md line 119 shows `TECHDEBT-01 | Phase 6 (gap closure) | Complete` and line 120 shows `AP-01 | Phase 7 (gap closure) | Complete`. All v1.0 audit gap-closure work (Phases 6 and 7) is finished.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| ---- | ---- | ------- | -------- | ------ |
| — | — | None found | — | `grep -n -E "TODO|FIXME|XXX|HACK|PLACEHOLDER"` on both modified files returned no matches |

No stubs, empty handlers, or hardcoded-empty returns found. `budgetObligationItems()` body is verified identical in logic to the pre-phase `openItems()` body (D-01 honored — GitHub Issue #3 untouched).

### Human Verification Required

None. This phase changes only backend domain-service logic with full Pest coverage; no new UI, visual, or real-time behavior was introduced. The Filament page/CSV/Excel consumers were not modified and were already verified in Phase 5.

### Gaps Summary

No gaps found. All must-haves from the plan frontmatter verified against the actual codebase:
- D-01, D-02, D-03 from CONTEXT.md implemented exactly as specified.
- Public `BudgetObligation`/`payment_order_id` path confirmed byte-for-byte unchanged (extraction only, no logic edits).
- `AccountsPayable::openItems()` public signature/return type unchanged; no controller, route, or Filament page changes.
- 3 new tests added covering inclusion (net amount), full-payment exclusion, and combined public+private rows; 8 pre-existing tests (5 openItems + 3 page/CSV) pass unmodified.
- Full suite: 263 passed, 886 assertions, 0 failed (matches expected baseline 260 + 3 new).
- `vendor/bin/pint --dirty --format agent` clean.
- AP-01 and TECHDEBT-01 both marked Complete in REQUIREMENTS.md — all v1.0 audit gap-closure work is done.

---

_Verified: 2026-09-19_
_Verifier: Claude (gsd-verifier)_

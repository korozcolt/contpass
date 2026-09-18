---
phase: 05-exportaci-n-excel-fase-d
plan: 01
subsystem: reports
tags: [openspout, xlsx, excel-export, pest, tdd]

# Dependency graph
requires:
  - phase: none
    provides: n/a (first plan of Fase D, no prior-phase dependency)
provides:
  - "App\\Services\\Reports\\ExcelReportExporter::handle() — agnostic headers/rows array to typed .xlsx StreamedResponse"
affects: [05-02, 05-03, 05-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Excel export service: array<headers>/array<rows> in, typed StreamedResponse out — no per-report knowledge; DateTimeInterface values get a dd/mm/yyyy Style, numeric values pass through untouched"
    - "openToFile('php://output') inside response()->streamDownload() closure, never openToBrowser() (which calls header() directly and conflicts with Laravel's own header handling)"

key-files:
  created:
    - app/Services/Reports/ExcelReportExporter.php
    - tests/Feature/ExcelReportExporterTest.php
  modified: []

key-decisions:
  - "No new dependency: openspout/openspout v4.32 was already vendored transitively (via filament/actions); used as-is per project's no-new-dependency constraint"

patterns-established:
  - "writeAndReopenXlsx() Pest test helper: captures StreamedResponse bytes via sendContent()+ob_start(), writes to a temp file, re-opens with OpenSpout\\Reader\\XLSX\\Reader to assert native cell types (float/int/DateTimeInterface) — reusable pattern for 05-03's report-specific Excel tests"

requirements-completed: [XLSEXPORT-02]

# Metrics
duration: ~25min
completed: 2026-09-18
---

# Phase 05 Plan 01: ExcelReportExporter Summary

**Shared `ExcelReportExporter` service converts any `headers`/`rows` array into a native-typed `.xlsx` download (numeric cells for int/float, date cells styled `dd/mm/yyyy` for `DateTimeInterface`) using the already-vendored `openspout/openspout` v4.32, with no new Composer dependency.**

## Performance

- **Duration:** ~25 min
- **Completed:** 2026-09-18T16:51:19Z
- **Tasks:** 2 completed
- **Files modified:** 2 (both created)

## Accomplishments
- `App\Services\Reports\ExcelReportExporter::handle()` — injectable service, single public method, agnostic to report shape (D-03/D-05)
- Native cell typing verified via round-trip through `OpenSpout\Reader\XLSX\Reader`: int/float → numeric cells, `Carbon`/`DateTimeInterface` → date cells (dd/mm/yyyy style), string/null → text/empty cells
- 4 Pest tests covering cell typing (numeric, date, string/null) and response headers (content-type, filename)

## Task Commits

Each task was committed atomically (TDD RED → GREEN):

1. **Task 1: Write failing Pest test for ExcelReportExporter** - `662c3af` (test)
2. **Task 2: Implement ExcelReportExporter to make the test pass** - `b5f0683` (feat)

_No REFACTOR commit needed — implementation matched the plan's verified-against-vendor-source code exactly; only Pint's `new_with_parentheses` fixer ran (folded into the Task 2 commit)._

## Files Created/Modified
- `app/Services/Reports/ExcelReportExporter.php` - Service with `handle(string $filename, array $headers, array $rows): StreamedResponse`, per-value `instanceof DateTimeInterface` check decides date styling, no per-column config
- `tests/Feature/ExcelReportExporterTest.php` - 4 tests + `writeAndReopenXlsx()` helper (byte-capture + re-open pattern, reusable in 05-03)

## Decisions Made
- Used `openToFile('php://output')` (never `openToBrowser()`) — confirmed via 05-RESEARCH.md that `openToBrowser()` calls PHP's `header()` directly, which conflicts with Laravel's `StreamedResponse` header handling
- No per-column style maps (`Row::fromValuesWithStyles()`) — kept the service fully agnostic per D-05; only the per-value `DateTimeInterface` check decides styling

## Deviations from Plan

None - plan executed exactly as written. Pint's automatic `new_with_parentheses` fixer (`new Style()` → `new Style`, `new Writer()` → `new Writer`) is a pure style normalization already required by CLAUDE.md ("run `vendor/bin/pint --dirty --format agent`"), not a functional deviation.

## Issues Encountered

**Worktree environment bootstrap (not a plan deviation, but required before any task could run):** this worktree (`agent-aebe3bf6f848a3844`) was created pointing at a stale commit, 5 commits behind `main`, with no `.planning/`, `vendor/`, `.env`, or `node_modules/` — the same pattern documented repeatedly in `.planning/STATE.md` across all prior phases of this project. Verified `git merge-base --is-ancestor HEAD main` (true, strict ancestor, no local-only commits at risk) and resolved with `git merge main --ff-only` (non-destructive fast-forward), then ran `composer install`, `cp .env.example .env && php artisan key:generate`. Deliberately did **not** run `npm install && npm run build` since this plan touches no frontend surface and Pest's DB config (`phpunit.xml`) uses sqlite in-memory, requiring no Postgres setup either.

Full regression run (`php artisan test --compact`, no filter): 237 tests, 234 passed, 3 failed — all 3 failures are pre-existing `ViteManifestNotFoundException` in `ExampleTest`/`WelcomePageTest` (welcome page rendering), caused by the intentionally-skipped `npm run build`, unrelated to this plan's changes. Logged in `.planning/phases/05-exportaci-n-excel-fase-d/deferred-items.md` per scope-boundary rules rather than fixed (out of scope for a backend-only plan).

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- `ExcelReportExporter` is ready to be injected into `AccountingReportController` in 05-03, which will supply the actual per-report `headers`/`rows` arrays for the 6 existing CSV reports
- 05-02 (per research plan sequencing) can proceed independently; no blockers from this plan
- If any later plan in this phase needs the welcome-page test group green, run `npm install && npm run build` in this worktree first (tracked in deferred-items.md)

---
*Phase: 05-exportaci-n-excel-fase-d*
*Completed: 2026-09-18*

## Self-Check: PASSED

- FOUND: app/Services/Reports/ExcelReportExporter.php
- FOUND: tests/Feature/ExcelReportExporterTest.php
- FOUND: .planning/phases/05-exportaci-n-excel-fase-d/05-01-SUMMARY.md
- FOUND: 662c3af (test commit)
- FOUND: b5f0683 (feat commit)

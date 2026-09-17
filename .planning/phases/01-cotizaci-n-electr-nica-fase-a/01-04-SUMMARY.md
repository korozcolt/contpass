---
phase: 01-cotizaci-n-electr-nica-fase-a
plan: 4
subsystem: quotations-pdf
tags: [dompdf, filament, pdf-export, quotation]

# Dependency graph
requires:
  - phase: 01-cotizaci-n-electr-nica-fase-a plan 1
    provides: "Quotation/QuotationLine models (number, expires_on, notes, total, lines/thirdParty/company relations)"
  - phase: 01-cotizaci-n-electr-nica-fase-a plan 3
    provides: "QuotationsTable, ViewQuotation page (recordActions/getHeaderActions insertion points)"
provides:
  - "resources/views/pdf/quotation.blade.php — DomPDF branding contract for the phase"
  - "quotations.pdf named route — authenticated PDF download endpoint"
affects: []

# Tech tracking
tech-stack:
  added: ["barryvdh/laravel-dompdf ^3.1"]
  patterns:
    - "Pdf::loadView(view, data)->download(filename) is the correct barryvdh/laravel-dompdf v3.1 facade API — Pdf::view() does not exist on this version despite being a common pattern in older docs/examples"
    - "Binary file downloads from a Filament table/page action use ->url()->openUrlInNewTab() (real <a href>, full GET request) instead of ->action() (Livewire AJAX call, which cannot return a raw binary response) — same pattern as the existing accounting-reports CSV exports"

key-files:
  created:
    - resources/views/pdf/quotation.blade.php
    - app/Http/Controllers/QuotationPdfController.php
    - tests/Feature/QuotationPdfTest.php
  modified:
    - composer.json
    - composer.lock
    - routes/web.php
    - app/Filament/Resources/Quotations/Tables/QuotationsTable.php
    - app/Filament/Resources/Quotations/Pages/ViewQuotation.php

key-decisions:
  - "Fixed plan's controller example: Barryvdh\\DomPDF\\Facade\\Pdf::view() does not exist in v3.1.2 (confirmed via composer show + vendor source inspection of PDF.php); the correct method is loadView(). Rule 1 auto-fix, verified by all 3 QuotationPdfTest cases passing."

requirements-completed: [QUOT-04]

# Metrics
duration: ~35min
completed: 2026-09-16
---

# Phase 01 Plan 4: Quotation PDF download Summary

**DomPDF-rendered quotation PDF (logo, company/client data, line-item table, amber-accented totals box, conditional terms section) served through an authenticated `quotations.pdf` route and exposed via "Descargar PDF" actions on both the quotations table and detail view — closing QUOT-04 and completing all 7 requirements of Phase 01.**

## Performance

- **Tasks:** 2 completed
- **Files created:** 3
- **Files modified:** 5

## Accomplishments
- Installed `barryvdh/laravel-dompdf` v3.1.2, the one new Composer dependency approved for this milestone (per `PROJECT.md` Key Decisions)
- `resources/views/pdf/quotation.blade.php` implements the full 01-UI-SPEC.md visual contract: 20mm page margins, `DejaVu Sans` fallback font, inline `<style>` (no Tailwind), amber `#D97706` reserved strictly to the title/accent-rule/total, `#F5F5F4` for the line-table header and totals box background, `pt`-based type scale (18/12/9/10), and 4px-multiple spacing tokens
- Terms/notes section is omitted entirely via `@if($quotation->notes)` when blank, per D-02 — no empty-state placeholder shown
- `QuotationPdfController::show()` eager-loads `lines`/`thirdParty`/`company` and streams the PDF as a download named after the quotation number
- `quotations/{quotation}/pdf` route added to `routes/web.php` at the end of the file, using the exact same closure + `abort_unless($request->user() !== null, 403)` pattern as the existing `accounting-reports.*` CSV routes (no route middleware, consistent with house convention)
- "Descargar PDF" action added to `QuotationsTable` (after `EditAction`, before lifecycle actions) and to `ViewQuotation::getHeaderActions()` (before `EditAction`) — both use `->url()->openUrlInNewTab()` since a binary download cannot be returned through a Livewire AJAX action call
- `QuotationPdfTest` (3 cases, all passing): authenticated download returns `application/pdf`; PDF renders successfully when `notes` is null; guest request is blocked with 403

## Task Commits

Each task was committed atomically:

1. **Task 1: Install dompdf + PDF Blade view** — `6862284` (feat)
2. **Task 2: Controller + route + "Descargar PDF" actions + test** — `59b0e0a` (feat)

**Plan metadata:** committed together with this SUMMARY (see final commit)

## Files Created/Modified
- `resources/views/pdf/quotation.blade.php` - DomPDF view, 5 sections (logo/title, company+client data, line table, totals box, conditional terms), full UI-SPEC color/type/spacing contract
- `app/Http/Controllers/QuotationPdfController.php` - `show()` renders `pdf.quotation` and returns a download response
- `routes/web.php` - `quotations.pdf` named route, authenticated via closure `abort_unless`
- `app/Filament/Resources/Quotations/Tables/QuotationsTable.php` - "Descargar PDF" row action
- `app/Filament/Resources/Quotations/Pages/ViewQuotation.php` - "Descargar PDF" header action
- `tests/Feature/QuotationPdfTest.php` - 3 feature tests covering auth, blank notes, guest block
- `composer.json` / `composer.lock` - `barryvdh/laravel-dompdf` ^3.1 dependency

## Decisions Made
- `Barryvdh\DomPDF\Facade\Pdf::view()` (as written in the plan's controller snippet) does not exist on the installed v3.1.2 facade — confirmed via `composer show barryvdh/laravel-dompdf` and reading `vendor/barryvdh/laravel-dompdf/src/PDF.php`, which only exposes `loadView()`. Swapped to `Pdf::loadView(...)->download(...)`, verified by the full `QuotationPdfTest` suite passing (Rule 1 auto-fix — plan example referenced a nonexistent method, a straightforward bug, not an architectural change).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Worktree was 32 commits behind `main`, missing `.planning/`, `vendor/`, `.env`, and built frontend assets**
- **Found during:** environment setup, before Task 1
- **Issue:** This worktree's branch pointed at an old commit (`39cc548`) while `main` was 32 commits ahead (`a803b88`), including all of Plans 1–3's work this plan depends on. Confirmed via `git merge-base` that the worktree HEAD was a strict ancestor of `main` (0 divergent commits on the worktree side), so a fast-forward was safe. No `vendor/`, `.env`, or compiled `public/build/` assets existed either.
- **Fix:** `git merge --ff-only main`, `cp .env.example .env`, `composer install`, `php artisan key:generate`, `npm install --ignore-scripts && npm run build`.
- **Files modified:** none tracked by this plan (environment setup only)
- **Verification:** baseline `php artisan test --compact --filter=Quotation` after setup: 15/15 passing; full suite after Task 2 and asset build: 176/176 passing.

**2. [Rule 1 - Bug] `composer require`'s `post-update-cmd` hook (`boost:update`) silently overwrote `CLAUDE.md`/`AGENTS.md`, stripping the project's custom "Documentation Files" and "Laravel Herd" sections**
- **Found during:** immediately after `composer require barryvdh/laravel-dompdf`
- **Issue:** `CLAUDE.md` itself states "no debe sobreescribirse ni perderse al generar/refrescar documentación de proyecto" — the Boost `post-update-cmd` regenerated both files from the package's default template, dropping this project's checked-in customizations (git diff confirmed exact content loss, not just reordering).
- **Fix:** `git checkout -- CLAUDE.md AGENTS.md` to restore the committed versions before proceeding; no further composer/artisan commands were run that could re-trigger the hook after this point in the same session.
- **Files modified:** none (reverted, not committed)
- **Verification:** `git diff CLAUDE.md AGENTS.md` empty after restore.

**3. [Rule 1 - Bug] `Barryvdh\DomPDF\Facade\Pdf::view()` does not exist on v3.1.2**
- **Found during:** Task 2, first `QuotationPdfTest` run
- **Issue:** Plan's controller snippet called `Pdf::view(...)`, which raised `UnexpectedValueException: Method [view] does not exist on PDF instance.`
- **Fix:** Changed to `Pdf::loadView(...)`, the correct method on the installed facade (confirmed via vendor source).
- **Files modified:** `app/Http/Controllers/QuotationPdfController.php`
- **Commit:** `59b0e0a`
- **Verification:** `php artisan test --compact --filter=QuotationPdfTest` → 3/3 passing.

**4. [Rule 3 - Blocking] `public/build/manifest.json` missing, causing 3 pre-existing Vite-related test failures unrelated to this plan's files**
- **Found during:** full-suite verification after Task 2
- **Issue:** `ExampleTest` (2 cases) and `WelcomePageTest` (1 case) failed with `ViteManifestNotFoundException` because this worktree's frontend assets were never built.
- **Fix:** `npm install --ignore-scripts && npm run build` (already needed for Deviation 1's setup; re-verified the manifest existed after Task 2's changes too).
- **Files modified:** none tracked (gitignored `public/build/` artifact only)
- **Verification:** full `php artisan test --compact` → 176/176 passing, 0 failures.

---

**Total deviations:** 4 auto-fixed (2 blocking environment setup, 1 blocking bug in a generated config file, 1 bug in plan-provided code)
**Impact on plan:** All four were environment-only or a one-line method-name fix; no plan scope, architecture, or test coverage changed.

## Issues Encountered
None beyond the deviations above. Full suite: 176/176 passing, `vendor/bin/pint --dirty --format agent` clean, `composer show barryvdh/laravel-dompdf` confirms `v3.1.2` installed.

## User Setup Required

None — no external service configuration required for this plan's code. (This worktree's local `.env`/`vendor/`/built assets are gitignored environment scaffolding, not committed; a fresh checkout still needs the standard `composer install && npm install && npm run build && php artisan migrate` steps documented in `CLAUDE.md`.)

## Next Phase Readiness
- QUOT-04 closed; all 7 requirements of Phase 01 (QUOT-01 through QUOT-07) are now complete.
- `quotations.pdf` route and "Descargar PDF" actions are live on both the table and detail view; no blockers for phase transition.

---
*Phase: 01-cotizaci-n-electr-nica-fase-a*
*Completed: 2026-09-16*

## Self-Check: PASSED

All 4 created/summary files verified present on disk. Both task commits (`6862284`, `59b0e0a`) verified present in `git log`.

# Stack Research

**Domain:** Commercial-market accounting features (Laravel 13 + Filament v5 + PHP 8.4) — quotation PDFs, ReteICA parametrization, bank statement CSV reconciliation, Excel export, future e-invoicing hook
**Researched:** 2026-09-16
**Confidence:** HIGH (both headline recommendations verified against this project's own `composer.lock`, not just training data)

## Critical Finding: openspout and league/csv are already installed — no new dependency needed for Fase D

`filament/actions` v5.6.8 (already required by `filament/filament` 5.6, confirmed in `composer.lock`) transitively requires:

- `openspout/openspout` **v4.32.0** (constraint `^4.23`) — used internally by Filament v5's native `ExportAction`/`Tables\Actions\ExportBulkAction` to generate XLSX
- `league/csv` **9.28.0** (constraint `^9.27`) — used internally by Filament v5's native `ImportAction` to parse CSV

This is the single most important fact for Fase D and Fase B: **the "best current package" question already has a locked answer inside this codebase's vendor tree.** No `composer.json` change is required to use OpenSpout directly — Composer only needs `openspout/openspout: ^4.23` added explicitly if you want to `use` its classes without relying on Filament's internal resolution (PHP won't complain about using a transitive dependency's classes, but making it a direct `require` is good practice so a future Filament upgrade that drops the dependency doesn't silently break the report pages). This still needs the one-line composer.json addition to satisfy the project's "no dependency change without approval" policy — but it downloads nothing new.

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| `openspout/openspout` | `^4.23` (locked at v4.32.0) | XLSX writer for Fase D report exports | Already vendored via `filament/actions`; true zero-cost addition. Streams rows to disk instead of building an in-memory workbook object graph — memory stays flat (~3 MB) regardless of row count, vs. PhpSpreadsheet-based tools which hold the whole workbook in memory. Benchmarks (PHP 8.4, mid-2026) show ~55x less peak memory than Laravel Excel for a 10k-row/20-col XLSX export. API shape (`Writer::create()` → loop → `addRow()`) is a near-identical drop-in for the existing `fputcsv`-in-a-loop pattern already used in `AccountingReportController::downloadCsv()` and `BudgetExecutionReport::exportCsv()`. |
| `barryvdh/laravel-dompdf` | `^3.1` (latest 3.1.2) | PDF generation for Fase A quotations | Pure PHP, zero system dependencies (no Node/Chromium sidecar) — fits Herd local dev and a plain Laravel Cloud PHP worker without extra infra. A quotation is a structured business document (header, line-items table, totals, terms) that doesn't need CSS Grid/Flexbox; DomPDF's CSS 2.1 subset is sufficient with a dedicated print stylesheet (not the app's Tailwind utility classes). Most mature/battle-tested option in the ecosystem for this exact "server-rendered Blade → PDF" use case. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `league/csv` | `^9.27` (locked at 9.28.0) | Robust CSV parsing for Fase B bank statement import | Already vendored via `filament/actions`. Handles the real-world mess of Colombian bank export CSVs better than raw `fgetcsv`: stream character-set conversion (many bank exports are Windows-1252/ISO-8859-1, not UTF-8), configurable delimiter (`;` is common in LATAM exports), BOM stripping, and a fluent `Reader`/`Statement` API for column mapping. Same zero-new-dependency situation as OpenSpout. |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| `vendor/bin/pint --dirty --format agent` | Format any new PHP (export helpers, PDF controller, CSV importer) | Already project standard, no change needed. |
| Pest feature tests | Verify export/import byte-for-byte or structurally | For XLSX, assert via `OpenSpout\Reader\XLSX\Reader` reading the generated file back, not string-matching binary content. For PDF, assert response headers/status and that the service that builds quotation data (not DomPDF's rendering) behaves correctly — don't snapshot-test rendered PDF bytes. |

## Installation

```bash
# Fase D — make the already-vendored transitive dependency explicit
composer require openspout/openspout:^4.23

# Fase A — PDF generation (new dependency, needs approval per project policy)
composer require barryvdh/laravel-dompdf:^3.1

# Fase B — make the already-vendored transitive dependency explicit
composer require league/csv:^9.27
```

No `npm` changes needed — none of these touch the frontend bundle.

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|--------------------------|
| `openspout/openspout` direct usage | `maatwebsite/excel` (Laravel Excel) v4.0.3, built on `phpoffice/phpspreadsheet` ^5.9 | Use Laravel Excel if the export needs to be driven straight off an Eloquent query with minimal glue code (`FromQuery`, `WithMapping`, queued chunked exports for very large datasets), or if you need to *read*/import complex multi-sheet XLSX with formulas/charts. Neither applies here — the report pages already pre-compute `$headers`/`$rows` PHP arrays with running balances and aggregations before the CSV step, so there's no query to hand to an `Exportable` class, and adopting Laravel Excel would mean writing 8-9 new `Export` classes to replicate what `downloadCsv()` already does in one shared helper. It also pulls in the much larger `phpoffice/phpspreadsheet` (full DOM-based spreadsheet engine, no streaming writer) as a genuinely new, heavy dependency instead of reusing what's already vendored. |
| `openspout/openspout` direct usage | Filament v5 native `ExportAction`/`Exporter` class | Use Filament's built-in export action for standard Resource **Table** exports (list of Eloquent records, one row per model, filtered/sorted by the table). It is model-and-query-coupled by design (`getColumns()` maps to Eloquent attributes/relations) and is the right tool if a future phase adds exportable Filament Resource tables (e.g., a `Quotation` list). It does **not** fit Fase D's existing report *Pages*, which aggregate/group data server-side into plain arrays before display — forcing that shape into an `Exporter`'s query-based model would be a bigger rewrite than adding one `downloadXlsx()` sibling to the existing `downloadCsv()` helper. |
| `barryvdh/laravel-dompdf` | `spatie/laravel-pdf` v2.13.1 (multi-driver: Browsershot/Chromium, Gotenberg, Cloudflare Browser Rendering, WeasyPrint, or DomPDF) | Switch to `spatie/laravel-pdf` with its **Browsershot driver** if the quotation template needs to reuse the app's actual Tailwind 4 components/utility classes for pixel-perfect, on-brand client-facing output. That requires shipping a Node + headless Chromium (~300 MB) sidecar, which is a real infrastructure commitment (must be supported on the Laravel Cloud target, adds cold-start weight, and is a bigger surface to keep patched). Notably, `spatie/laravel-pdf` *can* also run its DomPDF driver as a "zero-dependency" option — worth adopting later instead of `barryvdh/laravel-dompdf` directly if the team wants driver flexibility (start on DomPDF, flip to Browsershot later) rather than a hard package swap. Not recommended as the starting choice here because it adds an abstraction layer over DomPDF for no immediate benefit — `dompdf/dompdf` is only a `suggest`, not bundled, in `spatie/laravel-pdf`, so you'd still explicitly install and configure DomPDF either way. |
| `barryvdh/laravel-dompdf` | `pxlrbt/filament-excel` v4.1.0 (Filament v5-compatible: `filament/tables ^5.0`) wrapping `maatwebsite/excel` | Only relevant if Fase D exports move to a standard Filament Resource Table (bulk/header `ExportAction`) rather than the current custom report Pages. It still depends on `maatwebsite/excel ^3.x|^4.x`, so it inherits the same "new heavy dependency, doesn't fit the array-based report pattern" downside above. |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `maatwebsite/excel` (Laravel Excel) as the primary Fase D library | Adds `phpoffice/phpspreadsheet` (full in-memory workbook model, no true streaming writer) as a brand-new heavy dependency when an equivalent, already-vendored, lower-memory streaming writer (`openspout`) exists. Also forces restructuring 8-9 simple array-based report methods into `Exportable` classes for no functional gain. | `openspout/openspout` (already vendored via `filament/actions`) |
| `pxlrbt/filament-excel` for the existing report Pages | It's designed around Filament Table bulk/header actions operating on an Eloquent-backed table, not custom report Pages that stream pre-aggregated arrays. Using it here would mean bending the report pages into a Table-shaped export just to use the plugin, and it still pulls in `maatwebsite/excel` underneath. | Direct `openspout` usage alongside the existing `downloadCsv()` pattern |
| `spatie/browsershot` / Chromium-based PDF rendering for Fase A as a *first* implementation | Requires a Node + headless Chromium sidecar in production; adds real operational risk (binary size, cold starts, patching cadence) for a document (quotation) whose layout needs are fully covered by CSS 2.1 tables. Introduce only if/when a redesign genuinely needs Tailwind-fidelity PDFs. | `barryvdh/laravel-dompdf` with a dedicated print stylesheet |
| Raw `fgetcsv()`/manual string-splitting for Fase B bank statement parsing | Colombian bank CSV exports vary in encoding (often Windows-1252/ISO-8859-1) and delimiter (`;` vs `,`); manual parsing silently mangles accented characters and misparses amount columns using comma decimals. | `league/csv` (already vendored via `filament/actions`) with an explicit stream encoding filter |

## Stack Patterns by Variant

**If Fase D exports stay as custom Filament Pages (current architecture):**
- Add a `downloadXlsx(string $filename, array $headers, array $rows, array $numberFormats = [])` helper next to the existing `downloadCsv()` in `AccountingReportController` (and mirror it in `BudgetExecutionReport`), backed directly by `OpenSpout\Writer\XLSX\Writer`.
- Because it's the same file, the "which report gets Excel" decision stays a one-line call-site change per report method — no new classes per report.
- Use `Style::withFormat('#,##0')` / `Style::withFormat('dd/mm/yyyy')` on the relevant columns so currency and date columns become real numeric/date Excel cells (sortable, summable) instead of the plain text strings the current CSV export produces — this is a genuine quality upgrade over CSV, not just a format change.

**If a future phase adds an Eloquent-backed exportable Resource table (e.g., a `Quotation` list view):**
- Use Filament v5's native `ExportAction` + a custom `Exporter` class for that specific resource — it's already built in, needs no new dependency, and is the idiomatic Filament v5 way to do table-scoped exports (`formats([ExportFormat::Xlsx])` to force XLSX-only if CSV choice isn't wanted).

**If quotation PDFs later need real Tailwind-styled rendering (branding push):**
- Migrate from `barryvdh/laravel-dompdf` to `spatie/laravel-pdf` with the Browsershot driver, keeping the same Blade view — the migration cost is mostly composer + Node/Chromium infra, not template rewrites, since `spatie/laravel-pdf`'s API is view-based like DomPDF's.

## Version Compatibility

| Package A | Compatible With | Notes |
|-----------|-----------------|-------|
| `filament/actions` v5.6.8 | `openspout/openspout` `^4.23` | Do **not** require `openspout/openspout` at its latest major (v5.11.3 as of Sept 2026, requires PHP `~8.4\|\|~8.5`) — Filament pins `^4.23`. Requiring v5 directly would create a version conflict with `filament/actions` until Filament itself upgrades its constraint. Pin `^4.23` to match what's already resolved. |
| `filament/actions` v5.6.8 | `league/csv` `^9.27` | Same situation — league/csv 9.28.0 already satisfies this; no version conflict risk since 9.x is the only major in play. |
| `barryvdh/laravel-dompdf` v3.1.2 | Laravel `^9\|^10\|^11\|^12\|^13`, PHP `^8.1`, `dompdf/dompdf ^3.0` | Fully compatible with this project's Laravel 13.8 / PHP 8.4. |
| `spatie/laravel-pdf` v2.13.1 (if chosen later) | PHP `^8.2`, Laravel `^11\|^12\|^13` | Compatible, but `dompdf/dompdf` and `spatie/browsershot` are only `suggest`-ed, not bundled — must be required explicitly for whichever driver is chosen. |

## Sources

- Project `composer.lock` (`/Volumes/NAS(MAC)/Data/Herd/contpass/composer.lock`) — verified `openspout/openspout` v4.32.0 and `league/csv` 9.28.0 are already resolved, both required by `filament/actions` v5.6.8. HIGH confidence (primary source, not training data).
- `composer why openspout/openspout` / `composer why league/csv` run against the project — confirmed the dependency chain directly. HIGH confidence.
- Existing code: `app/Http/Controllers/AccountingReportController.php`, `app/Filament/Pages/BudgetExecutionReport.php` — confirmed the current CSV export pattern (`downloadCsv()` helper streaming `fputcsv` over pre-computed `$headers`/`$rows` arrays) that Fase D's XLSX helper should mirror. HIGH confidence.
- https://packagist.org/packages/openspout/openspout — latest version v5.11.3, PHP `~8.4||~8.5`, MIT/Apache-2.0 mixed license. MEDIUM confidence (used to confirm the *latest* release exists, but project should pin to the `^4.23` already locked by Filament, not this).
- https://packagist.org/packages/maatwebsite/excel — v4.0.3, PHP `^8.3`, Laravel `^12|^13`, depends on `phpoffice/phpspreadsheet ^5.9`. MEDIUM confidence.
- https://packagist.org/packages/barryvdh/laravel-dompdf — v3.1.2, PHP `^8.1`, Laravel `^9|^10|^11|^12|^13`, `dompdf/dompdf ^3.0`. MEDIUM confidence.
- https://packagist.org/packages/spatie/laravel-pdf — v2.13.1, PHP `^8.2`, Laravel `^11|^12|^13`; drivers confirmed via GitHub README (Browsershot, Gotenberg, Cloudflare Browser Rendering, WeasyPrint, DomPDF, chrome-php). MEDIUM confidence.
- https://packagist.org/packages/league/csv — v9.28.0, PHP `^8.1.2`. MEDIUM confidence.
- https://filamentphp.com/docs/5.x/actions/export — confirmed native `ExportAction`/`Exporter` is Eloquent-query-coupled, uses `OpenSpout\Writer` + `XlsxExportContentGenerator` internally, supports `formats([ExportFormat::Xlsx])` to restrict output format. MEDIUM-HIGH confidence (official docs).
- https://github.com/openspout/openspout (docs) — confirmed `Style::withFormat()` API for native XLSX number/date formatting. MEDIUM confidence (community-fetched doc excerpt, not Context7-verified).
- https://github.com/pxlrbt/filament-excel — v4.1.0, `filament/tables ^4.0|^5.0`, depends on `maatwebsite/excel ^3.x|^4.x`. MEDIUM confidence.
- WebSearch: OpenSpout vs Laravel Excel memory benchmark (10k rows × 20 cols, PHP 8.4, July 2026: ~55x less peak memory, flat regardless of row count) — LOW-MEDIUM confidence (single blog-style source, not independently reproduced here, but directionally consistent with OpenSpout's documented streaming-writer architecture vs PhpSpreadsheet's DOM-based model).

---
*Stack research for: Commercial-market accounting features (quotations, ReteICA, bank reconciliation CSV import, Excel export, e-invoicing hook)*
*Researched: 2026-09-16*

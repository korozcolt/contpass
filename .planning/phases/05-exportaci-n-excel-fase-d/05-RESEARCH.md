# Phase 5: Exportación Excel (Fase D) - Research

**Researched:** 2026-09-18
**Domain:** openspout 4.32 XLSX writer API, Laravel 13 streamed downloads, Filament 5 report pages
**Confidence:** HIGH (all findings verified directly against installed `vendor/openspout/openspout` source and installed Laravel/Filament source — no external docs needed, no training-data guesses)

## Summary

All architecture decisions for this phase are already locked in `05-CONTEXT.md` (D-01 through D-10). This research focuses purely on the mechanics the planner needs to write precise task steps: the exact openspout 4.32 API for constructing a `Writer`, writing typed cells (numeric/date/string), streaming output through Laravel's `response()->streamDownload()`, and the exact current source of the 6 controller methods / 6 Filament pages / routes file to refactor.

**Primary recommendation:** Use `OpenSpout\Writer\XLSX\Writer::openToFile('php://output')` (NOT `openToBrowser()`) inside the `streamDownload` callback — this writes the zip bytes directly to the output stream exactly like the existing `fputcsv` pattern, without openspout calling PHP's `header()` function itself (which would conflict with Laravel's header handling). Build each row with `OpenSpout\Common\Entity\Cell::fromValue($value, $style)`, passing a `Style` with `->setFormat('dd/mm/yyyy')` only when `$value instanceof DateTimeInterface` — this needs no per-column knowledge, matching the agnostic `headers`/`rows` shape from D-05. **Critical pitfall found:** `AccountingEntry::debit`/`credit` are cast `decimal:2`, which Eloquent returns as **strings**, not floats — the shared row-builders for `ledger()` and `thirdPartyMovements()` (the only two of the 6 reports using this model directly) must explicitly cast `(float)` before handing values to the Excel service, or XLSEXPORT-02's "native numeric cell" requirement silently fails for those two reports only.

## Project Constraints (from CLAUDE.md)

- PHP 8.4: explicit return types and param type hints on all new methods (service `handle()`/`write()`, controller row-builders).
- PHPDoc array shapes required for `array<string, mixed>` / `array<int, array<int, mixed>>` params (matches existing `downloadCsv()` doc block style).
- No inline comments except non-obvious WHY (e.g., a one-line comment on the `decimal:2` → string cast pitfall would qualify).
- Every change needs a new/updated Pest test; `php artisan test --compact` must pass; `vendor/bin/pint --dirty --format agent` must be run after any PHP edit.
- No new Composer/NPM dependency without approval — moot here, `openspout/openspout` v4.32.0 is already vendored (confirmed in `composer.lock` line 1245/4200, required by `filament/actions` v5.6.8).
- No new base folders without approval — `app/Services/Reports/` (or similar) is a new subfolder under the existing `app/Services/` root, consistent with `app/Services/Accounting`, `app/Services/Budget`; does not require a new *base* folder.
- Spanish (es_CO) UI strings — action label "Exportar Excel" (already specified in D-09), download filenames should follow the existing Spanish CSV filename convention (`libro-auxiliar.csv` → `libro-auxiliar.xlsx`, etc.).
- CLAUDE.md also requires `/gsd:execute-phase` as the entry point for implementation — not this researcher's concern, but planner should assume tasks run under that workflow.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **D-01:** Use `openspout/openspout` directly — already installed transitively via `filament/actions` v5.6.8 (confirmed v4.32 in `composer.lock`). Zero new dependency. `maatwebsite/excel` explicitly rejected.
- **D-02:** Do NOT use Filament v5's native `Exporter`/`ExportAction` — confirmed incompatible with 4 of 6 reports (those use `->records()` over arrays/`DB::table()`, not an Eloquent `Builder`). Its XLSX pipeline is also a CSV→XLSX reconversion, not a native typed writer.
- **D-03:** Excel-writing logic lives in **one shared service** (e.g. `app/Services/Reports/ExcelReportExporter.php` — exact name at Claude's discretion), reused by all 6 reports. Not one exporter per report.
- **D-04 (XLSEXPORT-03):** Each of the 6 relevant `AccountingReportController` methods is refactored to extract a **private method shared per report** (e.g. `ledgerRows(Company $company, ...): array`) that builds headers+rows ONCE. Both the existing CSV output and the new XLSX output call that same shared method — satisfies "no duplicated query logic" literally, including column-mapping duplication, not just the query itself.
- **D-05 (row shape):** The shared Excel service accepts an **agnostic** shape: `array<string> $headers` + `array<array<mixed>> $rows` — as simple as what already feeds `fputcsv()`. No typed per-cell DTOs (`ExcelCell::currency()`, etc.) — explicitly rejected as more new code than a 6-report phase warrants.

### Formato de celdas

- **D-06 (currency):** Plain numeric cells, no currency symbol, no special style applied via openspout. User can sum/sort/format in Excel themselves.
- **D-07 (date origin):** The shared method (D-04) delivers date values as **raw Carbon objects** in the row array (not pre-formatted strings). The CSV method keeps formatting to string at its own output point (as today); the Excel service writes the Carbon directly as a native date cell — no guessing types by column name/pattern.
- **D-08 (visible date format):** `d/m/Y` (common Colombian format) — explicitly preferred by the user over the `Y-m-d` used elsewhere in the app (Filament DatePickers, current CSV), prioritizing familiarity when opening the file in Excel/LibreOffice over internal UI format consistency. **This is an intentional deviation, not an oversight.**

### UI entry point

- **D-09:** Each of the 6 Filament report pages gets a new "Exportar Excel" button (`Action::make('exportExcel')->url(...)`) **alongside** the existing "Exportar CSV" button — same pattern as the current link, not a dropdown replacing both buttons.
- **D-10 (mechanism):** Download goes through a **new authenticated route** in `routes/web.php` pointing to a new method on `AccountingReportController` (e.g. `GET /accounting-reports/ledger.xlsx` next to `/accounting-reports/ledger`), with the same `abort_unless($request->user() !== null, 403)` gate as the existing CSV routes. No in-panel Filament `->action()`/`streamDownload()` — no precedent for that pattern in the codebase, and the route mechanism is already consistent with the existing CSV approach.

### Claude's Discretion

- Exact class/namespace name of the shared Excel service (D-03).
- Exact names of the shared private per-report methods (D-04) and of the `.xlsx` route names/paths.
- Visual style of the Excel sheet beyond what's decided (bold headers, frozen first row, column width) — not explicitly discussed, keep minimal unless trivial to add.
- Download filename (e.g. `libro-auxiliar-2026-09-18.xlsx`).

### Deferred Ideas (OUT OF SCOPE)

- **XLSEXPORT-04** (multi-sheet summary+detail export, e.g. Libro Mayor): already recognized as v2 in `.planning/REQUIREMENTS.md` — not discussed in depth, out of this phase.
- **Installing `maatwebsite/excel`**: considered as an alternative to D-01 and rejected — unnecessary new dependency when openspout is already vendored and covers what's needed.
- **COP currency-symbol cell formatting**: considered as an alternative to D-06 and rejected for implementation friction without clear benefit this phase.
- **Typed per-cell DTOs** (`ExcelCell::currency()`, etc.): considered as an alternative to D-05 and rejected — more new code than the 6-report scope warrants.
- **Filament in-panel streaming action and single export dropdown**: considered as alternatives to D-09/D-10 and rejected — no precedent in the code, the route + sibling button pattern is already consistent with existing CSV.
- **Exporting the 3 CSV-only reports out of scope** (`journal`, `financialStatements`, `bankReconciliation`): not part of XLSEXPORT-01 (which enumerates exactly 6 reports) — not touched this phase.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| XLSEXPORT-01 | Usuario puede exportar cada uno de los 6 reportes contables existentes en `.xlsx`, además del CSV ya existente | Confirmed exact current signatures/routes for all 6 target controller methods + all 6 Filament page header actions (see Code Context section) — precise before/after targets for new `.xlsx` routes + `exportExcel` actions |
| XLSEXPORT-02 | Columnas de moneda y fecha en el export de Excel son celdas numéricas/fecha nativas (ordenables/sumables), no texto | Confirmed exact openspout 4.32 API for typed cells (`Cell::fromValue()` auto-detection, `DateTimeCell`, `NumericCell`) + found the `decimal:2` cast pitfall that silently produces string cells for 2 of the 6 reports if not explicitly cast to float |
| XLSEXPORT-03 | El export de Excel reusa la misma fuente de datos que el export CSV de cada reporte (sin lógica de consulta duplicada) | Read full current source of `AccountingReportController` (all 9 methods, `downloadCsv()` helper) to give the planner exact extraction points for the D-04 shared row-builder refactor |
</phase_requirements>

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `openspout/openspout` | 4.32.0 (verified in `composer.lock`, exact commit `41f045c`) | Native streaming XLSX writer with typed cells | Already vendored transitively via `filament/actions` — zero install cost, exactly the API needed for typed numeric/date cells without a re-conversion pass |

No supporting libraries needed — no new Composer packages, per D-01/project constraint.

**Version verification:** `composer.lock` line 1245 declares `filament/actions` requires `"openspout/openspout": "^4.23"`; the resolved package entry (line 4200) pins `v4.32.0`. No `composer view` run needed — already installed and read directly from `vendor/openspout/openspout/`.

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Direct `OpenSpout\Writer\XLSX\Writer` | Filament `Exporter`/`ExportAction` | Rejected in CONTEXT.md D-02 — only fits 2/6 reports (Eloquent `Builder`-backed), and its XLSX output is a CSV→XLSX conversion pass, losing native cell typing |
| Direct `OpenSpout\Writer\XLSX\Writer` | `maatwebsite/excel` (PhpSpreadsheet-based) | Rejected in CONTEXT.md D-01 — real new dependency, unnecessary given openspout already covers the need |

## Architecture Patterns

### Recommended Project Structure

```
app/
├── Http/Controllers/
│   └── AccountingReportController.php   # 6 methods refactored (D-04) + 6 new *Xlsx() methods
├── Services/
│   └── Reports/
│       └── ExcelReportExporter.php      # NEW — shared writer service (D-03), name at discretion
routes/
└── web.php                              # +6 new `.xlsx` routes
app/Filament/Pages/
├── LedgerReport.php                     # +1 headerAction "Exportar Excel"
├── ThirdPartyMovementsReport.php        # +1 headerAction
├── TrialBalanceReport.php               # +1 headerAction
├── GeneralLedgerReport.php              # +1 headerAction
├── AccountsReceivableReport.php         # +1 headerAction
└── AccountsPayableReport.php            # +1 headerAction
```

### Pattern 1: Shared Excel writer service (D-03/D-05)

**What:** A single-responsibility service that turns `array<string> $headers` + `array<array<mixed>> $rows` into a downloadable XLSX byte stream, auto-detecting cell type per value (no per-column config).

**When to use:** Called once per `*Xlsx()` controller method, symmetric to how `downloadCsv()` is called today.

**Example (verified against installed openspout 4.32 source — `vendor/openspout/openspout/src/Writer/XLSX/Writer.php`, `src/Writer/AbstractWriter.php`, `src/Common/Entity/Cell.php`, `src/Common/Entity/Style/Style.php`):**

```php
<?php

namespace App\Services\Reports;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelReportExporter
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function handle(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $dateStyle = (new Style())->setFormat('dd/mm/yyyy');

            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $cells = array_map(
                    fn (mixed $value): Cell => Cell::fromValue(
                        $value,
                        $value instanceof \DateTimeInterface ? $dateStyle : null,
                    ),
                    $row,
                );
                $writer->addRow(new Row($cells));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
```

**Why `openToFile('php://output')` and not `openToBrowser()`:** `openToBrowser()` (`AbstractWriter::openToBrowser()`, line ~55-95) calls PHP's `header()` directly to set `Content-Type`/`Content-Disposition`/`Cache-Control`/`Pragma` and also calls `ob_end_clean()`. Laravel's `response()->streamDownload()` (`Illuminate\Routing\ResponseFactory::streamDownload()`) builds a `Symfony\Component\HttpFoundation\StreamedResponse` with its own headers array and sets `Content-Disposition` itself. Symfony sends all response headers via `sendHeaders()` **before** invoking the streamed callback. Calling `openToBrowser()` inside that callback would fire a second, conflicting round of `header()` calls (different filename-encoding logic, no `Content-Disposition` `attachment;` from Laravel's `makeDisposition()`) — fragile and duplicative. `openToFile('php://output')` instead just treats `php://output` as a plain writable stream (it's `fopen()`'d exactly like the existing CSV code's `fopen('php://output', 'w')` at `AccountingReportController::downloadCsv()` line 255), letting Laravel own all HTTP headers — exact parity with the existing CSV mechanism.

**Why this works without leaking temp files:** Internally, `AbstractWorkbookManager::close()` (`vendor/openspout/openspout/src/Writer/Common/Manager/AbstractWorkbookManager.php` line 142-148) always builds the XLSX's intermediate XML files in a real temp directory (`sys_get_temp_dir()` by default, via `OpenSpout\Common\TempFolderOptionTrait`), zips them, streams the zip bytes into whatever `$finalFilePointer` was passed to `openToFile()`/`openToBrowser()`, and then calls `cleanupTempFolder()` which recursively deletes that intermediate directory. This happens regardless of whether the final destination is a real file or `php://output` — no manual cleanup needed, and no separate `tempnam()`/`unlink()` dance required for the *output* file since we never materialize one.

### Pattern 2: Typed cell construction — the exact API surface

**What:** openspout auto-detects cell subtype from PHP value type via `Cell::fromValue()` (`vendor/openspout/openspout/src/Common/Entity/Cell.php` lines 42-64):

```php
public static function fromValue(bool|DateInterval|DateTimeInterface|float|int|string|null $value, ?Style $style = null): self
{
    if (is_bool($value))                 return new BooleanCell($value, $style);
    if (null === $value || '' === $value) return new EmptyCell($value, $style);
    if (is_int($value) || is_float($value)) return new NumericCell($value, $style);   // ← numeric, sortable/summable
    if ($value instanceof DateTimeInterface) return new DateTimeCell($value, $style); // ← Carbon implements this
    if ($value instanceof DateInterval)   return new DateIntervalCell($value, $style);
    if (isset($value[0]) && '=' === $value[0]) return new FormulaCell($value, $style, null);
    return new StringCell($value, $style);
}
```

- `int`/`float` → `NumericCell` → native Excel number, satisfies XLSEXPORT-02 for currency columns with **zero extra code**, as long as the value handed in is actually a PHP `int`/`float` (see pitfall below).
- Any `\DateTimeInterface` (Carbon, `CarbonImmutable`, native `DateTime`) → `DateTimeCell` → native Excel date serial, satisfies XLSEXPORT-02 for date columns.
- `string` → `StringCell` — this is the fallback that silently swallows the pitfall below.

**Custom date display format (D-08, `d/m/Y`):** `OpenSpout\Common\Entity\Style\Style::setFormat(string $format)` (verified, `vendor/openspout/openspout/src/Common/Entity/Style/Style.php` lines 443-449) sets the cell's Excel number-format code. **Excel format codes are NOT PHP `date()` tokens.** The Excel equivalent of PHP's `d/m/Y` is `dd/mm/yyyy` (Excel: `d`/`dd` = day, `m`/`mm` = month when not adjacent to an hour/second token, `yyyy` = 4-digit year). Use:

```php
$dateStyle = (new Style())->setFormat('dd/mm/yyyy');
```

There is no dedicated `DateCell`/`NumberFormat` class in this version — `Style::setFormat()` on a plain `Style` object applied to a `DateTimeCell` (via `Cell::fromValue($carbon, $dateStyle)`) is the entire mechanism.

### Pattern 3: Row construction shape (matches D-05's agnostic array shape)

`OpenSpout\Common\Entity\Row` (`vendor/openspout/openspout/src/Common/Entity/Row.php`) offers three construction paths:
- `new Row(array $cells, ?Style $rowStyle = null)` — cells already built (used in Pattern 1 above, needed because we must vary style per-cell based on type).
- `Row::fromValues(array $cellValues, ?Style $rowStyle = null)` — convenience wrapper that internally calls `Cell::fromValue()` with **no per-cell style** (fine for the header row, which is all strings).
- `Row::fromValuesWithStyles(array $cellValues, ?Style $rowStyle = null, array $columnStyles = [])` — convenience wrapper keyed by column index/key; **not used here** because it requires per-column knowledge, which D-05 explicitly rejected in favor of per-value type detection.

### Anti-Patterns to Avoid

- **Calling `openToBrowser()` inside a Laravel `streamDownload()` callback:** conflicting/duplicate HTTP headers (see Pattern 1).
- **Passing `$entry->debit`/`$entry->credit` straight into the row array without casting:** produces `StringCell`, not `NumericCell` — see Common Pitfalls below.
- **Building per-column style maps (`Row::fromValuesWithStyles($values, null, [3 => $dateStyle])`):** reintroduces per-report column coupling into the "agnostic" shared service that D-05 explicitly avoided. Prefer per-value `instanceof DateTimeInterface` detection instead.
- **Applying the date `Style` to non-date cells:** `Style::setFormat('dd/mm/yyyy')` would misrender numeric/string cells if applied unconditionally to every cell in a row — only pass `$dateStyle` when the value is a `DateTimeInterface`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Detecting whether a value should be a numeric vs. date vs. string Excel cell | A custom type-sniffing switch/match statement | `OpenSpout\Common\Entity\Cell::fromValue()` | Already does exactly this (int/float/DateTimeInterface/string) — verified in Pattern 2; a hand-rolled version would duplicate logic already in the vendored library |
| Producing valid `.xlsx` zip/XML structure | Manual `ZipArchive` + OOXML markup | `OpenSpout\Writer\XLSX\Writer` | This is the entire reason the dependency exists; the format's XML dialect (shared strings, styles.xml, sheet dimension refs, etc.) is non-trivial and already fully handled |
| Reading back written XLSX bytes in tests to assert cell types | Manual ZIP/XML parsing | `OpenSpout\Reader\XLSX\Reader::open($filePath)` (same package, already vendored) | Symmetric reader API — `$cell->getValue()` returns a `float`/`DateTimeInterface` when the cell was written as `NumericCell`/`DateTimeCell`, giving a direct, precise assertion for XLSEXPORT-02 without inspecting raw XML |

**Key insight:** openspout is a small, purpose-built library — every piece of "custom XLSX code" this phase might be tempted to write (zip structure, cell typing, number-format codes) is already implemented and vendored. The only genuinely new code this phase needs is: (1) the thin `headers`/`rows` → `Writer` adapter service, and (2) the per-report row-builder refactor (D-04), which is business logic extraction, not Excel-format logic.

## Common Pitfalls

### Pitfall 1: `decimal:2` Eloquent cast returns a string, silently breaking XLSEXPORT-02

**What goes wrong:** `AccountingEntry::debit` and `AccountingEntry::credit` are cast `'decimal:2'` (`app/Models/AccountingEntry.php`). Laravel's decimal cast returns a **string** (e.g. `"1234.56"`), not a `float`. If a row-builder passes `$entry->debit` straight through, `Cell::fromValue()`'s `is_int($value) || is_float($value)` check fails, and it falls through to `StringCell` — a text cell, not a numeric one, silently violating XLSEXPORT-02 ("celdas numéricas... no texto") for that column only.

**Why it happens:** The current CSV code (`AccountingReportController::ledger()` line 29-38, `thirdPartyMovements()` line 84-93) passes `$entry->debit`/`$entry->credit` directly into the row array with no cast — invisible in CSV because `fputcsv()` stringifies everything anyway, so this bug has zero observable effect today. It only becomes observable once the same row array feeds a typed Excel writer.

**How to avoid:** In the shared row-builder methods for `ledger()` and `thirdPartyMovements()` specifically (the only 2 of the 6 reports that read `debit`/`credit` off `AccountingEntry` directly — confirmed by reading all 6 methods' current source, see Code Context below), explicitly cast: `(float) $entry->debit`, `(float) $entry->credit`. The other 4 reports (`trialBalance`, `generalLedger`, `accountsReceivable`, `accountsPayable`) already build their row arrays with explicit `(float)` casts in existing code (confirmed by reading `AccountingReportController.php` and `app/Services/Accounting/{AccountsReceivable,AccountsPayable,FinancialStatement}.php`) — no change needed there, just don't regress them during the D-04 refactor.

**Warning signs:** A Pest test asserting the written `.xlsx`'s debit/credit cell values via `OpenSpout\Reader\XLSX\Reader` and getting a `string` back instead of `float`/`int` from `$cell->getValue()`.

### Pitfall 2: Excel number-format tokens are not PHP `date()` tokens

**What goes wrong:** Passing PHP's `'d/m/Y'` string directly to `Style::setFormat()` produces a cell that either fails to render as a date in Excel or renders with wrong/garbled formatting, because Excel's custom number-format mini-language uses different token semantics (`m` is ambiguous between "month" and "minutes" depending on context, single vs. double-letter tokens have different meaning, etc.).

**Why it happens:** It's an easy, unchecked, silently-wrong string to pass — nothing throws an exception for an invalid format code; Excel just displays something unexpected or shows `######`/raw serial numbers.

**How to avoid:** Use the Excel-native format code `dd/mm/yyyy` for `Style::setFormat()`, which renders equivalently to PHP's `d/m/Y` requirement from D-08. Do not reuse the PHP format string directly.

**Warning signs:** Opening the generated file in Excel/LibreOffice and seeing a raw number (date serial) instead of a formatted date, or a date rendered with an unexpected component order.

### Pitfall 3: `openToFile()` requires a plain writable stream/path — `zip` construction still touches real disk internally

**What goes wrong:** Assuming the entire XLSX write is purely in-memory/stream-based (like `fputcsv` to `php://output`) and therefore safe under restrictive filesystem sandboxes or read-only temp directories.

**Why it happens:** `openspout`'s `XLSX\Writer` always stages intermediate `.xml` files (worksheet content, styles, workbook manifest, etc.) inside a real directory under `sys_get_temp_dir()` (or whatever `Options::setTempFolder()` is configured to, default unset → `sys_get_temp_dir()`, confirmed via `TempFolderOptionTrait::getTempFolder()`) before zipping — this is invisible from the calling code but requires that directory to be writable.

**How to avoid:** No action needed in this app — Laravel Herd/standard PHP-FPM environments have a writable `sys_get_temp_dir()` by default, and the existing app has no sandboxing that would block it (no evidence found of restricted `open_basedir` or similar in `.env`/`php.ini` overrides). Flagged here only so the planner doesn't need to re-derive it if an export fails in an unusual deployment target (e.g. a locked-down container).

**Warning signs:** `OpenSpout\Common\Exception\IOException` on `$writer->close()` in an environment with a non-writable or missing temp directory.

### Pitfall 4: Empty report → still needs a valid `.xlsx`, and `accountsReceivable()`/`accountsPayable()` currently take no `Request` filters at all

**What goes wrong:** Assuming all 6 report methods accept the same filter parameters when building the new `.xlsx` routes/actions.

**Why it happens:** `AccountingReportController::accountsReceivable()` and `::accountsPayable()` currently have **no `Request $request` parameter at all** — confirmed by reading their current signatures (`public function accountsReceivable(): StreamedResponse`, `public function accountsPayable(): StreamedResponse`). Their Filament pages (`AccountsReceivableReport.php`, `AccountsPayableReport.php`) apply `third_party`/`bucket` filters only client-side (in `rows()`, via the page's `$filters` array passed by Filament's table `->records()` callback) — those filters are **never** forwarded to the CSV export route (`route('accounting-reports.accounts-payable')` is called with zero query params in the page's header action). The new `.xlsx` routes for these 2 reports should replicate this exactly: no request-driven filtering, full unfiltered `openItems()` dataset, matching current CSV behavior bit-for-bit (XLSEXPORT-03's data-source-reuse guarantee, not a new feature).

**How to avoid:** Do not "improve" filter-forwarding for these 2 reports as part of this phase — out of scope, would be a silent behavior change beyond XLSEXPORT-01/02/03.

**Warning signs:** A plan task that adds `Request $request` params to `accountsReceivable()`/`accountsPayable()` "to match the other 4 reports" — this is scope creep, not required by any XLSEXPORT-0X requirement.

## Code Examples

### Confirmed current query parameters per report (exact, for `.xlsx` route parity)

| Report | Controller method (current) | Request params read | Filament page filter source |
|---|---|---|---|
| Libro auxiliar (`ledger`) | `ledger(Request $request)` | `starts_on`, `ends_on`, `chart_account_id`, `third_party_id` (+ `export=1` flag for CSV branch) | `LedgerReport::reportQueryParameters()` |
| Balance de comprobación (`trialBalance`) | `trialBalance(Request $request)` | `starts_on`, `ends_on` (+ `export=1`) | `TrialBalanceReport::reportQueryParameters()` |
| Movimientos por tercero (`thirdPartyMovements`) | `thirdPartyMovements(Request $request)` | `starts_on`, `ends_on`, `third_party_id` (+ `export=1`) | `ThirdPartyMovementsReport::reportQueryParameters()` |
| Libro mayor (`generalLedger`) | `generalLedger(Request $request)` | `starts_on`, `ends_on` (no `export` flag — dedicated route always streams) | `GeneralLedgerReport::reportQueryParameters()` |
| Cartera de clientes (`accountsReceivable`) | `accountsReceivable()` | **none** — no `Request` param at all | N/A (page filters are client-side only, not forwarded) |
| Cuentas por pagar (`accountsPayable`) | `accountsPayable()` | **none** — no `Request` param at all | N/A (page filters are client-side only, not forwarded) |

Note the asymmetry between `ledger`/`trialBalance`/`thirdPartyMovements` (which gate CSV output behind an `export=1` query flag on the *same* route as the HTML view) vs. `generalLedger`/`accountsReceivable`/`accountsPayable` (dedicated routes that always stream, no view branch). The new `.xlsx` routes should follow the **dedicated-route** pattern for all 6 (matching D-10's example `GET /accounting-reports/ledger.xlsx`), not the `export=1`-flag pattern, since D-10 explicitly specifies a new route per report.

### Current `downloadCsv()` helper (reference point for the new XLSX equivalent)

```php
// app/Http/Controllers/AccountingReportController.php, lines 248-264
/**
 * @param  array<int, string>  $headers
 * @param  array<int, array<int, mixed>>  $rows
 */
private function downloadCsv(string $filename, array $headers, array $rows): StreamedResponse
{
    return response()->streamDownload(function () use ($headers, $rows): void {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
}
```

### Current header action pattern in Filament report pages (all 6, same shape)

```php
// e.g. app/Filament/Pages/LedgerReport.php, lines 99-104 — with query params
->headerActions([
    Action::make('export')
        ->label('Exportar CSV')
        ->icon(Heroicon::ArrowDownTray)
        ->url(fn (): string => route('accounting-reports.ledger', array_merge($this->reportQueryParameters(), ['export' => 1]))),
])
```

```php
// e.g. app/Filament/Pages/AccountsPayableReport.php, lines 107-112 — no query params
->headerActions([
    Action::make('export')
        ->label('Exportar CSV')
        ->icon(Heroicon::ArrowDownTray)
        ->url(fn (): string => route('accounting-reports.accounts-payable')),
])
```

The new `exportExcel` action should sit as a sibling entry in the same `headerActions([...])` array, using the same `url()`/`route()` pattern pointed at the new `.xlsx` route name, and the same conditional `reportQueryParameters()` merge where the current CSV action uses it.

### Current route registration pattern (all 9, same shape)

```php
// routes/web.php, lines 11-15
Route::get('accounting-reports/ledger', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->ledger($request);
})->name('accounting-reports.ledger');
```

New `.xlsx` routes should follow this exact closure-wrapping-controller-call pattern (not `Route::get(...)->controller()`, to stay consistent with the other 9 routes in the file), e.g.:

```php
Route::get('accounting-reports/ledger.xlsx', function (Request $request): mixed {
    abort_unless($request->user() !== null, 403);

    return app(AccountingReportController::class)->ledgerXlsx($request);
})->name('accounting-reports.ledger.xlsx');
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|---------------|--------|
| CSV-only export (`fputcsv` to `php://output`) | CSV + native-typed XLSX (openspout `Writer`) | This phase | Users get sortable/summable currency and date columns in Excel without manual re-typing; no change to existing CSV behavior |

**Deprecated/outdated:** Nothing in this phase deprecates existing functionality — CSV export is explicitly kept ("además del CSV ya existente" per phase goal).

## Open Questions

None — CONTEXT.md and direct source inspection resolved every mechanical question the planner needs. The only remaining judgment calls (exact service class name, exact private method names, exact download filenames, sheet visual polish) are explicitly delegated to Claude's discretion in D-03/D-04/D-09's Claude's Discretion section, not open research questions.

## Environment Availability

Skipped — no external tool/service/runtime dependency beyond what's already installed (PHP 8.4, Composer-managed `openspout/openspout` v4.32.0, already vendored and verified present in `vendor/openspout/openspout/`). No install step, no version upgrade, no fallback needed.

## Sources

### Primary (HIGH confidence — all read directly from the installed, vendored source in this repo)

- `vendor/openspout/openspout/src/Writer/XLSX/Writer.php` — `Writer` construction, `Options`
- `vendor/openspout/openspout/src/Writer/AbstractWriter.php` — `openToFile()`/`openToBrowser()`/`addRow()`/`close()` lifecycle, header-emission behavior of `openToBrowser()`
- `vendor/openspout/openspout/src/Writer/AbstractWriterMultiSheets.php` — `closeWriter()` delegation to `WorkbookManager`
- `vendor/openspout/openspout/src/Writer/Common/Manager/AbstractWorkbookManager.php` — `close()` lifecycle, temp-folder creation/cleanup (`cleanupTempFolder()`)
- `vendor/openspout/openspout/src/Writer/XLSX/Helper/FileSystemHelper.php` — confirms real-disk staging of intermediate XML before zip, `zipRootFolderAndCopyToStream()`
- `vendor/openspout/openspout/src/Common/Entity/Cell.php` — `Cell::fromValue()` type-detection logic (source of Pattern 2)
- `vendor/openspout/openspout/src/Common/Entity/Cell/{NumericCell,DateTimeCell}.php` — confirmed constructor signatures
- `vendor/openspout/openspout/src/Common/Entity/Row.php` — `Row::fromValues()`/`fromValuesWithStyles()`/constructor
- `vendor/openspout/openspout/src/Common/Entity/Style/Style.php` — `setFormat()` signature (line 443-449)
- `vendor/openspout/openspout/src/Common/TempFolderOptionTrait.php` — default temp folder = `sys_get_temp_dir()`
- `vendor/openspout/openspout/src/Reader/AbstractReader.php` — `Reader::open(string $filePath)` signature (for test-assertion guidance)
- `vendor/laravel/framework/src/Illuminate/Routing/ResponseFactory.php` (line 244-265) — `streamDownload()` implementation, confirms header ownership by Symfony/Laravel before callback execution
- `composer.lock` (lines 1245, 4200-4278) — confirmed `openspout/openspout` v4.32.0 required by `filament/actions` ^4.23, zero-install confirmation
- `app/Http/Controllers/AccountingReportController.php` — full current source of all 9 report methods + `downloadCsv()` helper
- `routes/web.php` — full current source of all 9 existing routes
- `app/Filament/Pages/{LedgerReport,ThirdPartyMovementsReport,TrialBalanceReport,GeneralLedgerReport,AccountsReceivableReport,AccountsPayableReport}.php` — full current source of all 6 header actions and row-building methods
- `app/Models/AccountingEntry.php` — confirmed `'debit' => 'decimal:2', 'credit' => 'decimal:2'` casts (source of Pitfall 1)
- `app/Services/Accounting/{AccountsReceivable,AccountsPayable,FinancialStatement}.php` — confirmed existing `(float)` casts already present in the other 4 reports' row-building code
- `tests/Feature/{GeneralLedgerReportTest,AccountsPayableReportTest}.php` — confirmed existing Pest test pattern for CSV export assertions (`assertHeader('content-type', ...)`)

No MEDIUM or LOW confidence findings — this research required no external web search; everything needed was already present in the installed vendor code and application source.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — version confirmed directly in `composer.lock`, already installed, read source directly, no assumption from training data
- Architecture: HIGH — all patterns derived from reading actual installed openspout 4.32 source, not documentation/blog posts that might target a different version
- Pitfalls: HIGH — the `decimal:2` cast pitfall was found by cross-referencing the model source against the writer's type-detection logic, not inferred

**Research date:** 2026-09-18
**Valid until:** Indefinite for this exact codebase state (openspout API and the `decimal:2` cast are stable, unlikely to change before this phase is planned/executed). Re-verify only if `composer.lock`'s `openspout/openspout` version changes before planning starts.

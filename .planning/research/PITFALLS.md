# Pitfalls Research

**Domain:** Adding commercial/private-market features (quotation, municipal withholding tax, bank CSV reconciliation, e-invoicing hook, Excel export) to an existing Laravel 13 + Filament v5 accounting app with immutable double-entry vouchers.
**Researched:** 2026-09-16
**Confidence:** HIGH for codebase-specific findings (read from source); MEDIUM for Colombian tax rule specifics (WebSearch, verify with accountant/municipal statute before shipping Fase C); MEDIUM for CSV/bank import gotchas (cross-referenced across multiple sources, generic to the domain not ContPass-specific).

## Critical Pitfalls

### Pitfall 1: Filament v5 `$get()` returns enum instance, not `->value`, in conditional closures

**What goes wrong:**
Any new `visible()`/`required()`/`disabled()` closure that reads a `Select::make(...)->options(EnumClass::class)` field via `$get('field')` and compares it to `EnumClass::Case->value` (or a raw string) always evaluates to `false`. The field silently never shows — no exception, no log entry, tests that only check the model layer still pass.

**Why it happens:**
Filament v5 changed enum-backed `Select` behavior so `$get()` returns the resolved enum instance for schema state, not the raw value stored in the form. This is exactly [issue #1](https://github.com/korozcolt/contpass/issues/1) in this repo, already hit in `WarehouseMovementForm` and `BudgetModificationsForm`/`BudgetModificationsRelationManager`, fixed by comparing directly against the enum case (`$get('type') === MovementType::Outbound`).

**How to avoid:**
Every new conditional field added in this milestone must compare `$get()` against the enum case directly, never `->value` or a string literal. Concretely:
- Fase A: `Quotation`/`QuotationLine` form fields conditional on `status` (e.g. showing "reason" only when status is `Rejected`, or hiding line-editing once `Accepted`) must compare to `QuotationStatus::Rejected`, not `'rejected'` or `->value`.
- Fase C: if the ReteICA municipality field is conditionally required/visible based on `Company.public_entity_type` or a new "applies ICA" toggle backed by an enum, same rule applies.
- Fase E: if the external-invoice hook fields become conditionally visible based on an `InvoiceProvider` enum, same rule applies.

**Warning signs:**
A conditional field "does nothing" in the browser (never appears/hides) despite the model-level logic being correct and Pest tests passing. This is the exact failure signature already documented for issue #1 — it will not throw, log, or fail a test that only exercises the service/model layer.

**Phase to address:**
A, C, E (any phase introducing a new conditional field on an enum-backed Select). Not applicable to B (CSV import, no new enum-conditional form fields planned) or D (no new forms).

---

### Pitfall 2: `BuildVoucherNumber::next()` pattern is not company-scoped and is not concurrency-safe — copying it verbatim breaks Fase A's own stated goal

**What goes wrong:**
The roadmap for Fase A explicitly says quotation numbering should use "el mismo patrón que `BuildVoucherNumber`" to get consecutive numbering per company. But the actual implementation of `BuildVoucherNumber::next()` (`app/Services/Accounting/BuildVoucherNumber.php`) is:
```php
$next = Voucher::query()->where('type', $type->value)->count() + 1;
```
This counts **all vouchers of that type across all companies**, not per-company — the count has no `whereBelongsTo($company)` or `where('company_id', ...)` clause. It's also a plain count-then-format with no locking, so two quotations (or vouchers) created concurrently by different users can read the same count and receive duplicate numbers.

**Why it happens:**
The existing pattern was written when the app effectively served one company at a time, and correctness under concurrency was never a driver (Laravel Boost/Pint don't catch this; it only surfaces under real concurrent traffic or multi-company data).

**How to avoid:**
Don't copy `BuildVoucherNumber` verbatim for `QuotationResource`'s numbering — fix the scoping bug in the new `BuildQuotationNumber` (or shared numbering service) by adding `->whereBelongsTo($company)`, and wrap the read-count-and-insert in `DB::transaction()` with a `lockForUpdate()` on the max/count query (or a dedicated per-company sequence table) to close the race. If the team wants strict consistency with the existing pattern (to avoid scope creep), at minimum add company scoping and flag the concurrency gap as a known limitation shared with `BuildVoucherNumber` — but silently reusing the buggy pattern in a *new* commercial-facing feature (quotations sent to real customers) is worse than in internal warehouse movements, because a duplicate quotation number sent to two different clients is customer-visible.

**Warning signs:**
Two `Quotation` records (possibly for different companies, if multi-company ever activates) end up with the same number; load-test or concurrent-request test creating quotations simultaneously produces duplicates.

**Phase to address:**
A. Verify with a test that creates two quotations "concurrently" (e.g., via `DB::transaction` + manual race simulation, or at minimum a test asserting numbering is scoped per `company_id`).

---

### Pitfall 3: No idempotency guard on "Convertir a ingreso" — double-click creates two immutable vouchers for one quotation

**What goes wrong:**
`PostIncomeVoucher::handle()` has no idempotency check — it always creates a new `Voucher` + `IncomeRecord`. If the Filament action "Convertir a ingreso" is triggered twice (double-click, browser back-button resubmit, or two staff members clicking the same accepted quotation), the system produces two separate approved, immutable income vouchers for the same commercial transaction. Because vouchers are immutable once approved (core architectural invariant), this cannot be corrected by editing — it requires a manual adjustment note (nota de ajuste) after the fact, which is exactly the failure mode the "immutability over convenience" design is supposed to prevent.

**Why it happens:**
`PostIncomeVoucher` was designed as a generic reusable service with no knowledge of its caller's state; the existing callers (manual income entry) don't have a "already converted" state to check. Reusing it for quotation conversion introduces a new caller-side state machine requirement that doesn't exist yet.

**How to avoid:**
Before calling `PostIncomeVoucher::handle()` from the "Convertir a ingreso" action, the action must: (1) check `Quotation.status` is `Accepted` and not already `Converted`, inside the same `DB::transaction` as the voucher creation (not a separate pre-check, to close the race), (2) flip `Quotation.status` to a terminal `Converted` state and store the resulting `voucher_id`/`income_record_id` on the quotation as part of the same transaction, (3) hide/disable the conversion action once `Converted`. Add a unique constraint or application-level guard (e.g., unique index on `income_records.quotation_id` if that FK is added) as a second line of defense.

**Warning signs:**
Two `IncomeRecord`s referencing line items that sum to the same quotation total; customer complains of being invoiced twice; accounting notices duplicate revenue vouchers for the same third party on the same day with identical amounts.

**Phase to address:**
A. Verify with a Pest test that clicking "Convertir a ingreso" twice (or calling the underlying action method twice) only ever produces one voucher, and that the second attempt fails cleanly (validation error, not a silent no-op or a second voucher).

---

### Pitfall 4: `ApplyWithholdingRules` applies every matching active rule unconditionally — adding a municipality dimension without a matching filter will double- or triple-withhold

**What goes wrong:**
`ApplyWithholdingRules::handle()` (`app/Services/Accounting/ApplyWithholdingRules.php`) does not discriminate by withholding *type* (ReteFuente vs. ReteIVA vs. ReteICA) beyond a free-text `concept` column — it fetches **every** `WithholdingRule` for the company that is active, effective on the date, and above `minimum_base`, and applies all of them, summed. If Fase C adds a `municipality` (or `dane_municipality_code`) column to `WithholdingRule` and creates one row per municipality for ICA, but `ApplyWithholdingRules` isn't updated to filter "only the rule(s) matching the operation's municipality," every ICA rule for every municipality configured in the system would match simultaneously and stack on top of each other and on top of unrelated ReteFuente/ReteIVA rules for the same transaction.

**Why it happens:**
The current schema treats all withholdings as a flat, undifferentiated list scoped only by company + effective date + minimum base — there's no `type`/`tax_kind` enum on `WithholdingRule` today, so "ICA rule that doesn't apply to this operation's municipality" has no natural way to be excluded from the query without an explicit new filter.

**How to avoid:**
When extending `WithholdingRule` for Fase C: (1) add an explicit discriminator (e.g., a `WithholdingType` enum: `ReteFuente`/`ReteIVA`/`ReteICA`) rather than relying on the free-text `concept` string for filtering logic, (2) add the municipality dimension only to ICA-type rows, (3) update `ApplyWithholdingRules::handle()` (or add a variant) to accept the operation's municipality and filter ICA rules to `where('municipality', $municipality)->orWhereNull('municipality')` (national rules with no municipality apply everywhere; municipal ICA rules apply only to their municipality) — never let two ICA rules for *different* municipalities both match one operation. Add a test with two active ICA rules for two different municipalities and assert only the matching one is applied.

**Warning signs:**
An expense/income voucher shows more than one ICA-labeled withholding line for the same transaction; total withheld amount exceeds what a single ICA rate should produce; withholding certificate report totals don't reconcile against `rate × base` for a single municipality.

**Phase to address:**
C. This is the single highest-risk pitfall in the milestone because withholding tax errors are a legal/compliance issue (over- or under-withholding is reportable to DIAN/municipal tax authority), not just a UX bug.

---

### Pitfall 5: ReteICA municipal rates, minimum bases, and periodicity are not centrally published — hardcoding a single national table will be wrong for most municipalities

**What goes wrong:**
Unlike ReteFuente/ReteIVA (national, DIAN-published, uniform), ICA and ReteICA are set by each municipality's own *estatuto tributario* — rate (per-mil), minimum base, and withholding periodicity vary city by city and, within a city, by economic activity code. There is no single national API or table to pull this from. Teams that hardcode "the" ICA rate (e.g., copying Bogotá's or Medellín's schedule) silently produce wrong withholding for every other municipality.

**Why it happens:**
Colombian sources describe ICA generically ("rate expressed in per-mil, minimum base varies by municipality") but don't provide a canonical machine-readable table, because the source of truth is fragmented across ~1,100 municipal tax statutes. [Multiple sources confirm this: base gravable and rates are set locally, not nationally.]

**How to avoid:**
Fase C should treat the municipal ICA rate/minimum-base as **user-entered configuration per municipality**, not a system-provided lookup table — this matches the roadmap's own framing ("con opción de edición manual"). Do not attempt to auto-populate rates for all 1,100+ municipalities; ship with an empty/seed-only table and let the accountant enter the rate for their operating municipality (typically just 1–3 municipalities per client). Document in the UI that the rate must be verified against the municipal Secretaría de Hacienda's current resolution, since these change annually (often tied to UVT changes, sometimes independently).

**Warning signs:**
None will surface in the software itself — this is a "wrong number that looks plausible" risk, not a crash. Mitigate by making the source of the configured rate/base auditable (who entered it, when, reference document) rather than trying to detect it programmatically.

**Phase to address:**
C. Also resolve the still-open roadmap question ("¿municipio de la Company o del ThirdParty?") before building the schema — this affects whether the municipality lives on `WithholdingRule` alone (Company-scoped) or needs a lookup against `ThirdParty` as well, per the roadmap's own "pendiente de confirmar" note in `docs/roadmap-apolo.md`.

---

### Pitfall 6: Bank statement CSV encoding/date-format/duplicate issues are silent, not exceptions — an importer that "works" in dev testing will still corrupt data with real bank exports

**What goes wrong:**
Colombian bank CSV exports (Bancolombia, Davivienda, BBVA, etc.) commonly: (a) use `DD/MM/YYYY` or `DD-MM-YYYY` dates, not ISO format, (b) are encoded in Windows-1252/Latin-1 rather than UTF-8, sometimes with a BOM, which corrupts accented characters (ó, é, ñ) in transaction descriptions without throwing any error, (c) contain embedded newlines inside description fields that can split one transaction into two CSV rows when parsed naively, (d) show batch/lote transfers as a single bank line that corresponds to multiple `Payment` records (e.g., payroll disbursement, multiple supplier payments settled in one wire).

**Why it happens:**
CSV has no strict standard; encoding and locale are bank-specific and undocumented; failures are silent (garbled text, not an exception) because `fgetcsv`/`str_getcsv` will happily parse malformed encoding into mojibake rather than erroring.

**How to avoid:**
For the Fase B importer (reusing the `ArchiveMasterPreviewImporter` pattern per the roadmap): (1) detect and normalize encoding explicitly (`mb_detect_encoding` + `mb_convert_encoding` to UTF-8, strip BOM) before parsing rows, (2) make the date format configurable per bank/import (don't assume `Y-m-d`), with a preview step showing parsed dates before committing, (3) design the matching algorithm as **many-to-one and one-to-many capable** (a bank line can match a sum of several `Payment`s and vice versa) rather than strict 1:1 amount matching — this is explicitly a known real-world pattern the roadmap doesn't mention and the "amount + date ± tolerance + reference" matching described in the roadmap will fail silently (leave everything in "pending manual match") for any batched transfer, which is common for payroll/supplier-batch payments in Colombian banking, (4) treat non-UTF-8-clean or unparseable-date rows as **rejected with a visible error row**, not skipped silently — the user must see "3 rows could not be imported" rather than a smaller-than-expected import passing silently.

**Warning signs:**
Imported transaction descriptions show `Ã³`, `Â¿`, or `?` characters (mojibake) instead of accented Spanish; import completes but reconciliation "pending" count includes previously-matched batch transfers that should have matched; the same statement period imported twice produces duplicate pending lines instead of being rejected.

**Phase to address:**
B.

---

### Pitfall 7: Re-importing the same bank statement period creates duplicate pending lines — no natural key exists on `Payment` or in a new import table to detect this

**What goes wrong:**
Nothing in the current schema (`Payment` has `reconciled_at`, `reference`, `paid_on`, `amount`, `cash_account_id`, but no external bank-transaction identifier) prevents importing the same CSV file (or an overlapping date range from a re-exported statement) twice. Without a dedup key, the second import creates a full second set of "pending" import lines, and if the matching algorithm auto-confirms based on amount+date+reference, it could mark a *second*, spurious payment as reconciled, or duplicate a manual match the user already made.

**Why it happens:**
CSV bank statement exports don't ship with a globally unique transaction ID Colombian banks reliably include in every export; even when a reference number is present, users commonly re-export overlapping date ranges (e.g., "the whole month" exported both mid-month and at month-end).

**How to avoid:**
The new bank-statement-line table (needed for Fase B, not yet in the codebase) should enforce a composite unique constraint per `cash_account_id` on something like `(date, amount, reference, description_hash)` and reject/flag exact duplicates at import time with a clear "N rows already imported, skipped" message, rather than silently re-inserting them. Store the raw imported row (or a hash of it) so a second import of the same file is detected even before matching runs.

**Warning signs:**
Reconciliation "pending" list grows faster than expected after a re-import; the same `Payment` gets a `reconciled_at` timestamp reset by two different bank lines from two overlapping imports.

**Phase to address:**
B.

---

### Pitfall 8: Reusing the CSV `downloadCsv()` pattern's in-memory row array for Excel export defeats the purpose of choosing a streaming library

**What goes wrong:**
`AccountingReportController::downloadCsv()` builds `$rows` as a fully-materialized PHP array *before* handing it to `response()->streamDownload()` — the streaming only applies to the HTTP response, not to how the report data itself is assembled. If Fase D's Excel exporter wraps this exact pattern (report service returns a full `array`/`Collection` of rows, then the exporter writes it), then the memory-performance advantage of choosing `openspout/openspout` over `maatwebsite/excel` is moot — the bottleneck is the upstream `FinancialStatement`/`AccountsPayable`/etc. service collecting all rows into memory before the writer ever sees them, exactly the "no pagination consistency" issue already flagged in `.planning/codebase/CONCERNS.md` (`TrialBalanceReport` has no pagination, collects everything).

**Why it happens:**
The existing CSV export code was written for reports that, at current data volumes, comfortably fit in memory; naively wrapping the same row-building code with a new Excel writer looks like "just changing the output format" but silently reintroduces the exact scaling problem a streaming library was chosen to avoid.

**How to avoid:**
Design the Fase D exporter interface to accept a generator/cursor/lazy collection (`LazyCollection` via `->cursor()` or a generator function), not a pre-built array, for any report whose row count can grow unbounded (Libro Mayor, Libro auxiliar, movimientos por tercero) — even if the initial implementation for smaller reports (cuentas por pagar, cartera aging buckets, which are already bounded by open-item count) doesn't strictly need it. At minimum, benchmark the Excel export against the same report exported today as CSV for the client with the largest transaction volume, before shipping.

**Warning signs:**
Excel export for Libro Mayor times out or exhausts memory for a company with a full year of transactions, even though the CSV export of the same report works fine (because CSV's `fputcsv` inside the streaming closure was already effectively low-memory per-row, while the Excel writer receives a giant pre-built array).

**Phase to address:**
D.

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Reuse `BuildVoucherNumber`'s exact (non-company-scoped, non-locked) numbering logic for Fase A quotations | Fast to implement, matches existing pattern | Duplicate quotation numbers under concurrency or if multi-company ever activates; customer-visible (unlike internal warehouse numbers) | Never for Fase A — fix scoping/locking when copying the pattern |
| Store ReteICA municipality only on `Company` (domicilio fiscal), skip per-`ThirdParty` municipality | Simpler schema, faster to ship | Wrong if the applicable municipality is legally the place of the operation/service, not the company's registered address — a real compliance risk, not just a UX gap | Acceptable only if confirmed with the user/accountant that domicilio-based sourcing is legally correct for this client base — currently an open question in the roadmap, not yet decided |
| Manual-only CSV-to-`Payment` matching confirmation UI with no auto-match threshold tuning | Ships faster, avoids false-positive auto-reconciliation | Accountants stop trusting auto-match suggestions if tolerance is too loose (false matches) or too strict (nothing auto-matches, defeating the point) | Acceptable for v1 if auto-match is presented as a *suggestion* requiring one-click confirm, never silent auto-confirm |
| Add e-invoicing hook fields (Fase E) directly on `IncomeRecord` instead of a separate `ExternalInvoiceReference` model | Fewer files, faster | Couples a nullable, provider-specific concern (CUFE, provider name, PDF URL) to the core immutable accounting model; harder to extend when a real integration is added later (multiple providers, retries, webhook state) | Acceptable only if fields stay purely nullable/display-only metadata with zero business logic — as scoped in the roadmap ("captura manual", no API) |

## Integration Gotchas

Common mistakes when connecting to external services.

| Integration | Common Mistake | Correct Approach |
|-------------|-----------------|-------------------|
| Bank CSV exports (Bancolombia/Davivienda/BBVA/etc.) | Assuming one fixed column layout/encoding/date format works for all banks or all export options within one bank | Make column mapping, encoding, and date format configurable per import; preview parsed rows before committing |
| Future third-party e-invoicing providers (Siigo/Alegra/Factus) | Designing the Fase E hook fields around one specific provider's response shape (e.g., assuming CUFE is always present, or a specific PDF URL pattern) | Keep hook fields generic (`provider_name`, `external_reference`, `document_url`, nullable `raw_response` JSON) since no provider is chosen yet — the roadmap explicitly defers that choice |
| DIAN/municipal tax rate sources | Treating any single scraped/aggregated "ICA rate table" as authoritative without a manual verification step | Always let the accountant confirm/enter the rate; store an audit trail of who set it and when, since rates change and vary per municipality |

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|-----------------|
| In-memory row collection before CSV/Excel streaming (existing pattern in `AccountingReportController::downloadCsv`, `TrialBalanceReport`) | Export slows down or OOMs for large-volume clients | Use `LazyCollection`/cursor-based row generation for Fase D exports, especially Libro Mayor/auxiliar | Already flagged in `CONCERNS.md` at 100k+ entries; Fase D risks reintroducing it for a new export format |
| Bank statement matching via naive N×M amount+date comparison across all pending `Payment`s and all imported lines | Reconciliation screen becomes slow to load as pending-item backlog grows | Scope matching queries by `cash_account_id` and a bounded date window (already the query pattern in `BankReconciliation::payments()`); index the new bank-statement-line table on `(cash_account_id, date, amount)` | Noticeable once a company has months of unreconciled backlog or high transaction volume (100s/month) |
| `WithholdingRule` query in `ApplyWithholdingRules` scans all active rules per transaction with no caching | Negligible today (few rules per company) | If Fase C multiplies rule count by adding one row per municipality per client with multi-municipality operations, revisit indexing on `(company_id, is_active, starts_on, ends_on)` | Only relevant if a single company ends up with dozens of municipal ICA rules simultaneously — unlikely for typical single-municipality private clients, but possible for multi-branch clients |

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Trusting CSV file content (bank statement) without validating it belongs to the claimed `CashAccount`/`Company` | A user could upload a CSV for the wrong bank account (accidental or malicious) and have it silently reconcile against unrelated payments | Require explicit `CashAccount` selection before import, show account number/bank name from the file (if present) for user confirmation before committing matches |
| No policy layer (already flagged in `CONCERNS.md`) extended to new Fase A/B/C/D/E resources without a new dedicated policy | New commercial modules (quotations visible to sales-adjacent roles, ICA rates only editable by accounting) inherit the same "no granular authorization" gap, potentially exposing tax configuration or customer quotation data to any authenticated user | Since fixing the whole policy layer is out of scope for this milestone, at minimum audit which new Filament Resources should NOT be visible to every role and gate navigation visibility as a stopgap, consistent with existing `has_budgetary_control`-style guards |
| Embedding raw external-invoice document URLs (Fase E) without validating they point to the expected provider domain | Stored URL could be used for phishing/redirect if a malicious actor with income-record edit access enters an arbitrary URL | Treat the field as untrusted display data; consider domain allowlisting once a specific provider is chosen (deferred, per roadmap) |

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-------------------|
| Silent-fail conditional fields (Pitfall 1) presented with no visible error | Accountant thinks a feature is broken/missing, files a support request instead of getting an obvious validation message | Add explicit tests per enum case for every new conditional field; treat "field never shows" as a release blocker, not a minor bug |
| Auto-match reconciliation that silently commits matches instead of requiring confirmation | Accountant loses trust in reconciliation screen, reverts to fully manual work, defeating the point of Fase B | Always show auto-matched suggestions in a "confirm" state requiring explicit user action, per roadmap's own framing ("cruce automático... UI de confirmación manual para los que no calzan") |
| ICA rate entry with no indication of which municipality/period it applies to, once multiple municipalities are configured | Wrong rate applied without anyone noticing until a tax audit | Show municipality + effective date range prominently on every withholding line in vouchers/certificates, not just the amount |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Fase A "Convertir a ingreso" action:** Often missing idempotency/state guard — verify double-invocation cannot create two vouchers (Pitfall 3), and that quotation numbering is company-scoped and race-safe (Pitfall 2).
- [ ] **Fase C ReteICA calculation:** Often missing the municipality filter in `ApplyWithholdingRules` — verify with two active ICA rules for different municipalities that only the matching one applies (Pitfall 4), and that the Company-vs-ThirdParty municipality-sourcing decision is explicitly confirmed and tested, not left as an implicit assumption.
- [ ] **Fase B CSV importer:** Often missing encoding normalization, configurable date format, and duplicate-import detection — verify with a real bank export sample containing accented characters and a re-import of the same file (Pitfalls 6, 7).
- [ ] **Fase B matching algorithm:** Often missing many-to-one/one-to-many support for batched bank transfers — verify with a test case where one bank line's amount equals the sum of multiple `Payment`s.
- [ ] **Fase D Excel export:** Often missing streaming/lazy data assembly — verify memory usage on the largest real report (Libro Mayor for a full fiscal year), not just a small test dataset (Pitfall 8).
- [ ] **Fase E hook fields:** Often missing "no active integration" enforcement — verify no code path silently attempts to call an external API using these fields before a provider is actually chosen.

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|-----------------|-------------------|
| Duplicate income voucher from double-conversion (Pitfall 3) | HIGH | Vouchers are immutable — must create a nota de ajuste reversing the duplicate voucher, following the existing adjustment-note pattern; cannot delete/edit the erroneous voucher directly |
| Wrong/stacked ICA withholding applied to live vouchers (Pitfall 4, 5) | HIGH | Same as above — reversing adjustment note per affected voucher; additionally may require amended withholding certificates issued to the third party and correction filings with the municipal tax authority, which is outside the software's scope |
| Duplicate bank statement import (Pitfall 7) | MEDIUM | Add a cleanup path to bulk-delete unmatched/duplicate import lines by import batch ID; never delete a `Payment.reconciled_at` state automatically — require manual review before un-reconciling |
| Silent conditional-field failure shipped to production (Pitfall 1) | LOW | Straightforward code fix (compare to enum case) plus a regression test per case; no data corruption since the field simply didn't show, it didn't save wrong data |

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|--------------------|-----------------|
| Filament v5 enum `$get()` comparison bug | A, C, E | Pest/browser test per enum case confirming field visibility toggles correctly |
| Non-company-scoped, race-unsafe voucher/quotation numbering | A | Test creating quotations for two companies confirms independent sequences; concurrency test confirms no duplicate numbers |
| No idempotency on quotation→income conversion | A | Test invoking the conversion action twice confirms exactly one voucher is created and the second attempt is rejected |
| Unconstrained withholding rule stacking across municipalities | C | Test with two active ICA rules for different municipalities confirms only the matching one applies |
| Municipal ICA rate/base not centrally sourced, must be user-entered | C | Manual QA sign-off from an accountant on the entered rate before go-live per client; audit trail present |
| CSV encoding/date/duplicate-row handling | B | Test importing a real bank sample file with accented characters and mixed date formats; re-import same file confirms rejection/dedup |
| Batched/many-to-one bank transfer matching | B | Test case: one bank line amount equals sum of N `Payment`s, confirms suggested match |
| In-memory row collection defeating streaming Excel writer | D | Memory-usage benchmark on largest real report before and after Fase D ships |

## Sources

- `/Volumes/NAS(MAC)/Data/Herd/contpass/app/Services/Accounting/BuildVoucherNumber.php` (read directly — confirms non-company-scoped, non-locked numbering)
- `/Volumes/NAS(MAC)/Data/Herd/contpass/app/Services/Accounting/ApplyWithholdingRules.php` and `app/Models/WithholdingRule.php` (read directly — confirms no type/municipality discriminator today)
- `/Volumes/NAS(MAC)/Data/Herd/contpass/app/Services/Accounting/PostIncomeVoucher.php` (read directly — confirms no idempotency guard)
- `/Volumes/NAS(MAC)/Data/Herd/contpass/app/Http/Controllers/AccountingReportController.php` (read directly — confirms in-memory row array pattern for `downloadCsv`)
- `/Volumes/NAS(MAC)/Data/Herd/contpass/app/Services/Accounting/BankReconciliation.php`, `app/Models/Payment.php` (read directly — confirms no external bank-transaction identifier exists today)
- `/Volumes/NAS(MAC)/Data/Herd/contpass/.planning/codebase/CONCERNS.md` (existing known bugs #1/#2, unpaginated reports, weak multi-tenant policy layer)
- `/Volumes/NAS(MAC)/Data/Herd/contpass/docs/roadmap-apolo.md` (Fase A–E scope, open questions on ReteICA municipality source and Excel dependency approval)
- [¿Como se practica la retención de ICA en Colombia 2026? — Alegra](https://blog.alegra.com/colombia/certificado-retencion-de-ica/) (MEDIUM confidence — WebSearch summary, general ReteICA mechanics)
- [Tabla de ReteICA 2026 por ciudad — Leegales](https://leegales.com/reteica-retencion-en-la-fuente-en-el-ica/) (MEDIUM confidence — confirms per-municipality variability)
- [ReteICA en Colombia — Siigo](https://www.siigo.com/blog/obligaciones-fiscales/que-es-reteica-y-cuando-se-aplica/) (MEDIUM confidence)
- [Retención en la fuente por ICA — Gerencie.com](https://www.gerencie.com/retencion-en-la-fuente-en-el-ica.html) (MEDIUM confidence)
- Bank CSV import pitfalls (encoding, date format, duplicate detection) — cross-referenced across multiple accounting-software blog sources (Xero/QuickBooks-focused, generic to the domain, LOW-MEDIUM confidence, not Colombian-bank-specific but consistent with common CSV/encoding failure modes)
- [Performance | Laravel Excel official docs](https://docs.laravel-excel.com/4.x/exports/performance.html) and community reports on OpenSpout streaming memory behavior (MEDIUM confidence)

---
*Pitfalls research for: Laravel/Filament accounting app — commercial feature additions (quotations, municipal withholding tax, bank CSV reconciliation, e-invoicing hook, Excel export)*
*Researched: 2026-09-16*

# Project Research Summary

**Project:** ContPass — Milestone "Mejoras Comerciales para Mercado Privado"
**Domain:** Adding commercial/private-market features (quotations, municipal withholding tax, bank reconciliation import, e-invoicing hook, Excel export) to an existing Laravel 13 + Filament v5 accounting monolith with immutable double-entry vouchers
**Researched:** 2026-09-16
**Confidence:** HIGH

## Executive Summary

The 5 planned phases (A–E) are technically sound and the decided build order (A→C→B→E→D) has **no technical blocker** — all 5 features touch disjoint parts of the schema and can genuinely be built independently. The most important finding changes a stated assumption rather than the scope: the "shared DANE municipality catalog" the roadmap assumed already existed (reused from `Dependency`/`CompanySignatory`) **does not exist** — `Company` only has two free-text DANE code columns, nothing else in the app has any municipality infrastructure. Fase C must build this catalog from scratch, which revises its effort estimate upward slightly but doesn't change sequencing.

The second major finding directly resolved the ReteICA open question already flagged in `docs/roadmap-apolo.md`: Colombian market precedent (Siigo, Siesa) keys ReteICA by economic activity (CIIU) × municipality registered on the *ThirdParty*, not the paying company's domicile. This was surfaced to the user mid-research; **the user confirmed keeping the simpler Company-domicile model** for this milestone, a deliberate tradeoff now recorded in `PROJECT.md`.

The stack research produced a genuinely valuable correction: **no new dependency is needed for Fase D.** `openspout/openspout` and `league/csv` are already vendored transitively via `filament/actions` (Filament v5's own native table export/import machinery uses them). Only Fase A's PDF generation (`barryvdh/laravel-dompdf`) is a real new dependency requiring approval. The main risks are inherited bugs, not new ones: `BuildVoucherNumber`'s numbering pattern (which Fase A is told to copy) is not company-scoped and not concurrency-safe, `PostIncomeVoucher` has no idempotency guard (a double-click risk once reused for quotation conversion), and `ApplyWithholdingRules` applies every matching rule unconditionally (a real over-withholding risk once ICA rules are added without a municipality filter).

## Key Findings

### Recommended Stack

No new Composer dependency is required for Fase B (CSV parsing) or Fase D (Excel export) — `league/csv` v9.28.0 and `openspout/openspout` v4.32.0 are already resolved in `composer.lock` as transitive dependencies of `filament/actions` v5.6.8. Only `barryvdh/laravel-dompdf` (^3.1) for Fase A's quotation PDFs is a genuinely new dependency.

**Core technologies:**
- `openspout/openspout` (^4.23, already vendored): Fase D's XLSX writer — streams rows to disk (~3MB flat memory regardless of row count) vs. `maatwebsite/excel`'s in-memory `phpoffice/phpspreadsheet` model. Zero-cost to make explicit in `composer.json`.
- `league/csv` (^9.27, already vendored): Fase B's CSV parser — handles Colombian bank export quirks (Windows-1252 encoding, `;` delimiters, BOM) far better than raw `fgetcsv`.
- `barryvdh/laravel-dompdf` (^3.1, NEW — needs approval): Fase A's quotation PDF generator — pure PHP, no Chromium/Node sidecar, sufficient for a structured business document.

**Rejected for now:** `maatwebsite/excel` (Laravel Excel) — would add the much heavier `phpoffice/phpspreadsheet` as a brand-new dependency and force restructuring 6 array-based report methods into `Exportable` classes, for no functional gain over the already-vendored `openspout`.

### Expected Features

**Must have (table stakes):**
- Quotation: Draft→Sent→Accepted/Rejected→Expired lifecycle, sequential per-company numbering, PDF, one-way convert-to-income — matches Siigo/Alegra pattern exactly, already correctly scoped
- ReteICA: rate lookup keyed by municipality (activity-code granularity is the market standard but user confirmed deferring it — see Gaps below)
- Bank reconciliation: CSV column-mapping UI (banks export differently), deterministic amount+date-window+reference auto-match with mandatory manual confirmation, duplicate-amount collision handling
- External invoice hook: number, CUFE, provider, document URL — capture-only, never generate/validate
- Excel export: native currency/date cell formatting (the #1 complaint in accounting Excel exports is numbers/dates exported as text)

**Should have (competitive, defer to v1.x):**
- Quotation expiration dashboard badge
- Bulk-confirm for reconciliation batches
- Multi-sheet Excel export for reports with summary+detail split (Libro Mayor)

**Defer (v2+):**
- ReteICA-by-municipality analytics/reporting
- Reusable product/service catalog for quotation lines
- Active e-invoicing provider API integration (beyond the data hook)

**Explicit anti-features (do not build):** automatic DIAN/CUFE emission, OFX/live bank feeds, AI-assisted reconciliation matching (conflicts with the audit-first Core Value), ReteICA declaration/filing generation, full CRM around quotations, multi-currency.

### Architecture Approach

All 5 features integrate as additive components around the existing service-layer architecture — no existing service's public behavior needs to change except `ApplyWithholdingRules`, which gains a municipality filter branch. The critical correction: build a small standalone `municipalities` lookup table from scratch in Fase C (pure geography: DANE codes + names), keeping ICA rate/validity in `WithholdingRule` rows (reusing its existing `starts_on`/`ends_on`/`rate`/`minimum_base` versioning) rather than embedding a rate on the municipality table itself — otherwise two sources of truth emerge for the same concern.

**Major components:**
1. `Quotation`/`QuotationLine` + `BuildQuotationNumber` (new, company-scoped and concurrency-safe unlike the pattern it's modeled on) — feeds `PostIncomeVoucher` via a thin adapter action, never duplicating posting logic
2. `municipalities` lookup (new, built from scratch) + `WithholdingRule.dane_municipality_code` (new nullable column) + `ApplyWithholdingRules` municipality filter (modified)
3. `bank_statement_imports`/`bank_statement_lines` (new staging tables, persisted across multiple requests — NOT a single dry-run/commit transaction like `ArchiveMasterPreviewImporter`) + matching service that sets `Payment.reconciled_at` via the existing mutator
4. `ExternalInvoiceReference` (new, separate 1:1 model — NOT columns bolted onto `IncomeRecord`, to preserve its immutability boundary)
5. `downloadXlsx()` sibling to the existing `downloadCsv()` helper in `AccountingReportController` — reuses the same headers/rows arrays, no query duplication

### Critical Pitfalls

1. **Filament v5 `$get()` returns enum instance, not `->value`, in conditional closures** (already hit once, issue #1) — any new conditional field in Fases A/C/E must compare against the enum case directly. Silent failure, no exception, tests that only check the model layer still pass.
2. **`BuildVoucherNumber`'s pattern is not company-scoped or concurrency-safe** — copying it verbatim for Fase A's quotation numbering would produce duplicate, customer-visible quotation numbers under concurrent creation. Must add company scoping + locking when building `BuildQuotationNumber`, not blindly clone the bug.
3. **No idempotency guard on quotation→income conversion** — `PostIncomeVoucher` has no "already converted" check; a double-click produces two immutable vouchers for one sale, recoverable only via a nota de ajuste. Must guard status transition + voucher creation inside one transaction.
4. **`ApplyWithholdingRules` applies every matching active rule unconditionally** — adding municipality-scoped ICA rules without a matching filter will stack multiple municipalities' rates on the same transaction. Highest-risk pitfall in the milestone (legal/compliance exposure, not just a bug).
5. **Bank CSV encoding/date/duplicate issues fail silently, not with exceptions** — Colombian bank exports vary in encoding (Windows-1252), date format, and can represent batched transfers as one line matching N payments. An importer that "works" in dev testing can still corrupt data with real exports.

## Implications for Roadmap

Research confirms the roadmap's decided phase list and order (A→C→B→E→D) needs no structural change — only scope refinement within phases, based on the findings above.

### Phase A: Cotización electrónica
**Rationale:** No dependency on anything else; highest perceived commercial value per the original market research.
**Delivers:** `Quotation`/`QuotationLine` models, `QuotationResource`, PDF, company-scoped/concurrency-safe numbering, idempotent one-way "convertir a ingreso" action.
**Addresses:** Quotation lifecycle, numbering, PDF, conversion (all table stakes).
**Avoids:** Pitfalls 1 (enum comparison), 2 (numbering), 3 (idempotency) — all three must be explicitly tested in this phase, not assumed safe by pattern-copying.

### Phase C: ReteICA por municipio
**Rationale:** Self-contained, no dependency on Fase A/B; revised scope now includes building the municipality catalog from scratch (previously assumed to exist).
**Delivers:** New `municipalities` lookup table (seeded from public DANE list), `WithholdingRule.dane_municipality_code`, `ApplyWithholdingRules` municipality filter, `WithholdingRuleResource` municipality select field.
**Uses:** Existing `WithholdingRule` versioning pattern (`starts_on`/`ends_on`/`rate`/`minimum_base`) — extended, not replaced.
**Implements:** Company-domicile-based municipality sourcing (user-confirmed simplification vs. the ThirdParty/CIIU market standard).
**Avoids:** Pitfall 4 (unconstrained rule stacking) — mandatory test with two active ICA rules for different municipalities.

### Phase B: Conciliación bancaria por extracto CSV
**Rationale:** No dependency on Fase A/C; reuses existing `BankReconciliation`/`Payment.reconciled_at` read-side unchanged.
**Delivers:** `bank_statement_imports`/`bank_statement_lines` staging tables, CSV importer with encoding/date normalization and duplicate-import detection, matching service supporting many-to-one batched transfers, manual confirmation UI.
**Uses:** `league/csv` (already vendored) for parsing.
**Avoids:** Pitfalls 6 (encoding/date/duplicate rows) and 7 (re-import duplicates) — both require explicit handling, not assumed away by "just parse the CSV."

### Phase E: Hook de facturación externa
**Rationale:** Zero dependency either direction; lowest-effort phase, correctly placed as filler after the three feature-heavy phases.
**Delivers:** New `ExternalInvoiceReference` model (separate table, 1:1 to `IncomeRecord` — not bolted-on columns), manual capture UI in the Ingresos edit view.
**Implements:** Generic provider-agnostic fields (no assumption about which third-party provider is eventually chosen).

### Phase D: Exportación Excel dedicada
**Rationale:** Fully decoupled from A/B/C/E; only blocked on dependency approval (which, per stack research, is actually a zero-cost "make explicit" approval for `openspout/openspout`, not a real new install).
**Delivers:** `downloadXlsx()` sibling to `downloadCsv()` in `AccountingReportController`, wired to the 6 existing report pages, with native currency/date cell formatting.
**Uses:** `openspout/openspout` (already vendored via `filament/actions`).
**Avoids:** Pitfall 8 (in-memory row collection defeating the point of a streaming writer) — for unbounded reports (Libro Mayor, auxiliar), the exporter should accept a lazy collection/generator, not a pre-built array, even though the initial bounded reports (cartera, cuentas por pagar) don't strictly need it yet.

### Phase Ordering Rationale

- No feature depends on another's output — the order A→C→B→E→D is purely a value/effort prioritization, not a technical dependency chain, confirmed by direct code inspection of every touchpoint.
- Fase D is correctly last because it's the only phase gated by a non-technical blocker (dependency approval) rather than by any code dependency — and even that gate is now known to be lighter than assumed (explicit-vendoring approval, not a new install).
- Fase C's revised scope (build the municipality catalog from scratch) should be reflected in its plan's task list, not in the phase order.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase B:** many-to-one/one-to-many bank transfer matching logic deserves a dedicated design pass during plan-phase — this is genuinely non-trivial and under-specified in the original roadmap text.
- **Phase C:** the exact seed data source/process for the DANE municipality list should be pinned down during plan-phase (public DANE list format, how many municipalities to seed vs. lazy-add).

Phases with standard patterns (skip research-phase):
- **Phase A:** well-established quotation→invoice pattern, existing `PostIncomeVoucher` reuse is straightforward.
- **Phase E:** purely additive, no external integration yet — lowest-risk phase in the milestone.
- **Phase D:** the library question is now resolved (no new dependency for the writer itself); remaining work is mechanical (wire 6 report pages).

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Verified directly against this project's own `composer.lock`, not just training data or Packagist listings |
| Features | MEDIUM | WebSearch-verified against multiple LatAm vendors (Siigo, Alegra, World Office, Helisa); no official spec exists for quotation lifecycle since it's not DIAN-regulated |
| Architecture | HIGH | All findings verified by reading the actual named services/models in this codebase, not inferred from docs |
| Pitfalls | HIGH for codebase-specific findings (read from source); MEDIUM for Colombian tax rule specifics (recommend accountant sign-off before Fase C ships) |

**Overall confidence:** HIGH

### Gaps to Address

- **ReteICA activity-based (CIIU) granularity:** the market standard keys the rate by ThirdParty economic activity, not just municipality — user explicitly confirmed deferring this for simplicity (Company-domicile model). Documented as an explicit Out of Scope item in `PROJECT.md`; revisit if a specific client's compliance needs demand it.
- **Municipal ICA rates are not centrally published** — Fase C must treat rates as user-entered configuration per municipality (matching the roadmap's own "con opción de edición manual" framing), not attempt to auto-populate all ~1,100 Colombian municipalities. Needs an accountant sign-off step before go-live per client, outside the software itself.
- **Bank statement matching many-to-one support** is more complex than the roadmap's original "amount + date ± tolerance + reference" description — needs explicit design during Fase B's plan-phase, not just implementation of the simple case.

## Sources

### Primary (HIGH confidence)
- Project `composer.lock`, `composer why openspout/openspout`, `composer why league/csv` — direct verification of already-vendored dependencies
- Direct code inspection: `BuildVoucherNumber.php`, `ApplyWithholdingRules.php`, `PostIncomeVoucher.php`, `AccountingReportController.php`, `BankReconciliation.php`, `WithholdingRule.php`, `Company.php`, `Dependency.php`, `CompanySignatory.php`, `ThirdParty.php`, `Payment.php`, `IncomeRecord.php`
- Packagist: `openspout/openspout`, `league/csv`, `barryvdh/laravel-dompdf`, `maatwebsite/excel` — version/compatibility confirmation
- Filament v5 official docs (`filamentphp.com/docs/5.x/actions/export`) — confirms native `ExportAction` internals use OpenSpout

### Secondary (MEDIUM confidence)
- Siigo, Siesa, SysCafé documentation on ReteICA parametrization (activity × municipality pattern)
- DIAN technical annex on CUFE structure (confirms issuer-side-only generation, capture-only scope is correct)
- Multiple accounting-software blog sources on bank CSV reconciliation pitfalls (generic to the domain, not Colombian-bank-specific)

### Tertiary (LOW confidence)
- Single-source OpenSpout vs. Laravel Excel memory benchmark (directionally consistent with documented architecture, not independently reproduced)

---
*Research completed: 2026-09-16*
*Ready for roadmap: yes*

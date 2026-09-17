# Architecture Research

**Domain:** Adding 5 commercial features to an existing Laravel 13 + Filament v5 accounting monolith (ContPass)
**Researched:** 2026-09-16
**Confidence:** HIGH (all findings verified by reading the actual services/models named in scope, not inferred from docs)

## Standard Architecture

### System Overview

```
┌───────────────────────────────────────────────────────────────────────┐
│  Presentation: Filament Resources/Pages (app/Filament)                │
│  ┌───────────┐ ┌──────────────┐ ┌───────────┐ ┌────────────────────┐ │
│  │ Quotation │ │ WithholdingRule│ │ BankStmt  │ │ Report Pages       │ │
│  │ Resource  │ │ Resource (ext)│ │ Import UI │ │ (+ Export XLSX)    │ │
│  └─────┬─────┘ └───────┬────────┘ └─────┬─────┘ └─────────┬──────────┘ │
├────────┼───────────────┼─────────────────┼─────────────────┼─────────┤
│        ▼               ▼                 ▼                 ▼         │
│  Domain Services: app/Services/{Accounting,Imports}                   │
│  ┌────────────────┐ ┌────────────────────┐ ┌──────────────────────┐  │
│  │ PostIncome-     │ │ ApplyWithholding-   │ │ BankStatement        │  │
│  │ Voucher (reused)│ │ Rules (extended)    │ │ Importer (new,       │  │
│  │ ◄── ConvertQuo- │ │        ▲            │ │ modeled on Archive-  │  │
│  │     tation (new)│ │        │            │ │ MasterPreviewImporter│  │
│  └────────┬────────┘ │  Municipality       │ └──────────┬───────────┘  │
│           │           │  lookup (new,       │            │              │
│           │           │  NOT yet shared)    │            ▼              │
│           │           └────────────────────┘  BankReconciliation       │
│           ▼                                   (reused, unchanged API)  │
│      IncomeRecord ◄── Quotation/QuotationLine (new, 1:N)               │
│           │                                        │                   │
│           ▼                                        ▼                   │
│  ExternalInvoiceReference (new, 1:1) ──────►  Payment.reconciled_at    │
│  (Fase E hook, no active integration)         (existing column, set   │
│                                                by new matching flow)   │
└───────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | New / Modified / Reused |
|-----------|-----------------|--------------------------|
| `Quotation`, `QuotationLine` models + `QuotationResource` | Capture commercial quote, lines, status lifecycle, PDF | New |
| `BuildQuotationNumber` service | Sequential per-company numbering | New (clone of `BuildVoucherNumber` pattern) |
| Quotation "convert to income" action | Maps quote lines to `PostIncomeVoucher::handle()` args | New (thin adapter — no new posting logic) |
| `Municipality` lookup (table or enum-backed) | Single source of DANE department/municipality codes + ICA rate metadata | New — see Finding below, this does **not** already exist as shared infra |
| `WithholdingRule` (extended) | Add municipality dimension + ICA-specific fields | Modified |
| `ApplyWithholdingRules` | Filter/compute ICA alongside existing national withholdings | Modified |
| `BankStatementImport` staging model (e.g. `BankStatementLine`) | Persist parsed CSV rows pending manual/auto match | New — different shape than `ArchiveMasterPreviewImporter` (see Finding below) |
| `BankStatementCsvImporter` service | Parse CSV, upsert staging rows, run auto-match heuristic | New (loosely modeled on `ArchiveMasterPreviewImporter`'s parse/validate structure, but not its transactional dry-run/commit shape) |
| Match-confirmation UI (Filament action/page) | Let user confirm/reject auto-matches, set `Payment.reconciled_at` | New |
| `Payment.reconciled_at`, `BankReconciliation` | Existing reconciliation state and summary/pending-items logic | Reused unchanged |
| `ExternalInvoiceReference` model (or nullable columns on `IncomeRecord`) | Store external e-invoicing provider reference (number, CUFE, provider, URL) | New |
| Report Page export actions (`LedgerReport`, `TrialBalanceReport`, etc.) | Add "Exportar Excel" alongside existing "Exportar CSV" header action | Modified (6 pages) |
| New `AccountingReportController` methods or Filament-side export | Produce `.xlsx` using approved new dependency | New (depends on Fase D dependency approval) |

## Critical Finding: The Shared DANE Municipality Catalog Does Not Exist

The milestone brief assumed `Dependency`/`CompanySignatory` "already use a DANE municipality catalog" that Fase C could reuse. **This is not accurate in the current codebase:**

- `Company` (`app/Models/Company.php`) has two plain string columns, `dane_department_code` (2 chars) and `dane_municipality_code` (3 chars), added in `2026_07_30_120050_add_dane_codes_to_companies_table.php`. They are free-text, validated by length only — no foreign key, no lookup table, no seeded catalog.
- `Dependency` (`app/Models/Dependency.php`) has **no DANE fields at all** — just `company_id`, `name`, `is_active`.
- `CompanySignatory` (`app/Models/CompanySignatory.php`) has **no DANE fields at all** — just `area` (enum `SignatoryArea`), `full_name`, `position`, `identification`, `is_active`.
- `ThirdParty` has a free-text `city` string column, no DANE code, no relation to `Company`'s DANE fields.

So there is currently **zero shared municipality infrastructure** anywhere in the app — no `Municipality` model, no seeded DANE list, no ICA-rate-by-municipality table. Fase C cannot "reuse an existing catalog" because none exists; it must build one from scratch. This changes the Fase C estimate (low-effort assumption in the roadmap should be revisited) but does **not** change the phase order, since nothing else in Fase A/B/D/E depends on this catalog — building it inside Fase C is fine, no separate pre-phase extraction is needed. If a future phase (Secretaría, Rendición) also needs DANE geography, Fase C's new catalog becomes the thing to extend later — build it as a standalone lookup (not embedded inside `WithholdingRule`) so it's naturally reusable, without doing that extraction work now.

**Recommendation for Fase C's new catalog:**
- A small `municipalities` table (`dane_department_code`, `dane_municipality_code`, `name`, `department_name`) seeded once from the public DANE list, OR an enum-free simple lookup if only used for display/validation (no ICA rate varies by name, only by code+company config).
- ICA rate itself is not a DANE property — it is set by each municipality's ordinance. Model it as `WithholdingRule` rows scoped by municipality code (extend `WithholdingRule` with nullable `dane_municipality_code`, matching `Company.dane_municipality_code` string format already used), not as a property of the municipality lookup. This keeps `ApplyWithholdingRules`'s existing `effectiveOn`/`minimum_base`/`rate` shape intact — just add a municipality filter alongside the existing company/date filters.
- The roadmap's open question ("¿municipio de `Company` o de `ThirdParty`?") should resolve to `Company` for v1: `Company.dane_municipality_code` already exists and is populated via `CompanySettings`; adding a municipality dimension to `ThirdParty` would require a new column + catalog wiring on a second model for no clear near-term benefit. Extending to `ThirdParty` later is additive (nullable column, `ApplyWithholdingRules` falls back to `Company` when null) — not a rework risk.

## Feature-by-Feature Integration

### Fase A — Quotation

**New:** `Quotation`, `QuotationLine` models, `QuotationResource` (+ Pages/Schemas/Tables), `BuildQuotationNumber` service (clone `BuildVoucherNumber`'s `next(Type $type)` pattern — but Quotation has no `VoucherType`-like enum needed, just a per-company sequence), PDF view.

**Integration point:** the "Convert to income" action must call `PostIncomeVoucher::handle(Company $company, ThirdParty $thirdParty, array $data)` exactly as `CreateIncomeRecord` does today — passing `revenue_account_id`, `receivable_account_id`, `support_number`, `accrual_date`, `amount` derived from `QuotationLine` totals. **Do not** duplicate `PostsBalancedVoucher` logic in the Quotation flow; the conversion action is a thin translator from `Quotation` fields to `PostIncomeVoucher`'s expected array shape, same as any other `CreateIncomeRecord`-style page handler.

**Data flow:** `Quotation` (draft→sent→accepted/rejected/expired) is a standalone commercial record with **no accounting effect** until conversion. Only the conversion action touches `Voucher`/`IncomeRecord`. `Quotation` keeps a nullable `income_record_id` (or `voucher_id`) after conversion for traceability, mirroring how `Voucher.adjusts_voucher_id` links records without collapsing them into one table.

**Build order note:** no dependency on Fase C/B/E/D — can genuinely go first as decided.

### Fase C — ReteICA by municipality

**Modified:** `WithholdingRule` gains a nullable `dane_municipality_code` column (string, matches `Company.dane_municipality_code` format) and likely a `concept` value dedicated to ICA (the model already has a free-text `concept` fillable, so no enum change needed — confirm existing `concept` is a string, not enum-backed, which it is).

**Modified:** `ApplyWithholdingRules::handle()` needs a new parameter/branch: when the rule has a non-null `dane_municipality_code`, only apply it if it matches `$company->dane_municipality_code` (or in future, the third party's). This is a small `filter()` addition to the existing pipeline, not a rewrite — `effectiveOn()`, `minimum_base` and `rate`-based `amount` computation stay identical.

**New:** the `municipalities` lookup table/seed described above, plus a `Select` field in `WithholdingRuleResource`'s form to pick a municipality (options sourced from the new lookup, not free text, to avoid typo mismatches against `Company.dane_municipality_code`).

**Build order note:** confirmed no blocking dependency on Fase A/B. The catalog is self-contained inside this phase.

### Fase B — Bank statement CSV import → reconciliation

**Key architectural distinction from the roadmap's assumption:** `ArchiveMasterPreviewImporter` is a **single-shot, transactional, dry-run-or-commit** importer — it parses a whole JSON payload, does all upserts inside one `DB::transaction`, and either commits or rolls back atomically in one request. Fase B's requirement is fundamentally different: bank statement lines must be **persisted in a "pending match" state across multiple requests**, so a user can review and confirm matches over time (possibly days). A single dry-run/commit transaction cannot model this — there is no "later" for a rolled-back transaction.

**What to actually reuse from `ArchiveMasterPreviewImporter`:** its *parsing/validation/summary* structure (parse rows → validate/normalize → build a summary array with counts and a `rejected` list with reasons) is a good pattern to copy for the CSV parse step. Its *transactional dry-run* shape is not applicable — statement lines must be persisted immediately (status `pending`), not rolled back.

**New components:**
- `bank_statement_imports` (batch metadata: cash account, file, imported_at, imported_by) and `bank_statement_lines` (date, amount, description/reference, raw row, `status` enum: Pending/Matched/Ignored, nullable `payment_id`) tables + models.
- `BankStatementCsvImporter` service: parses CSV, creates one `BankStatementImport` + N `BankStatementLine` rows (status Pending). This *is* a straightforward `DB::transaction` for the insert batch itself (atomic import, not atomic reconciliation).
- `MatchBankStatementLines` service (or a method inside a matching service): for each Pending line, searches `Payment` rows on the same `cash_account_id` within a date/amount tolerance window and proposes matches; on confirmation, sets `Payment.reconciled_at` (reuse the existing `is_reconciled` mutator on `Payment`) and the line's `status`/`payment_id`.
- Filament UI: an import page/action (upload CSV) + a table of Pending lines with a "confirm match" row action, likely living alongside or extending `BankReconciliationReport`.

**Integration point:** `BankReconciliation`'s `pendingItems()`/`summary()` methods read `Payment.reconciled_at` and need **no changes** — the new flow only changes *how* `reconciled_at` gets set (via confirmed CSV match instead of manual `ToggleColumn` click), so `BankReconciliation` stays a pure read-side service exactly as today.

**Build order note:** no dependency on Fase A/C. Confirmed as position 3 is fine; if anything, doing Fase C first is good because both touch `Company`'s municipal/config surface loosely, but there's no hard technical link.

### Fase E — External e-invoicing hook

**New:** Either (a) nullable columns directly on `IncomeRecord` (`external_invoice_number`, `external_invoice_cufe`, `external_invoice_provider`, `external_invoice_url`), or (b) a separate `ExternalInvoiceReference` model with a 1:1 `belongsTo`/`hasOne` to `IncomeRecord`.

**Recommendation:** separate model. `IncomeRecord` is created programmatically inside `PostIncomeVoucher::handle()` and is treated as an immutable operational detail record tied to an immutable `Voucher` (same immutability philosophy as vouchers per `ARCHITECTURE.md`). Bolting mutable "captured later, edited later" fields directly onto `IncomeRecord` blurs that immutability boundary. A `hasOne` `ExternalInvoiceReference` (nullable, created/updated independently after the fact from the Filament Ingresos edit view or a relation manager) keeps `IncomeRecord` itself untouched and matches the existing pattern of `Voucher` → `incomeRecord`/`expenseRecord`/`payment` as separate related tables rather than growing one giant row.

**Integration point:** purely additive — no existing service changes. `IncomeRecordResource` gains a relation manager or a tab for capturing the reference manually; no automatic trigger.

**Build order note:** genuinely no dependencies either direction; correctly placed as low-effort filler in position 4.

### Fase D — Excel export

**Modified:** header actions on `LedgerReport`, `TrialBalanceReport`, `ThirdPartyMovementsReport`, `AccountsReceivableReport`, `AccountsPayableReport`, `GeneralLedgerReport` — each currently has one `Action::make('export')->url(route('accounting-reports.X'))` pointing at a CSV-streaming route in `AccountingReportController`. Adding Excel means either:
1. A second header action `Action::make('export-xlsx')` pointing at a twin route/controller method per report, reusing the exact same query-building code each report page/controller method already has (the row-building closures in `AccountingReportController` are already extracted per report — e.g. `accountsPayable()`, `generalLedger()`), or
2. A shared `ExportsToExcel` trait/service that takes the same `array<string> $headers, array<array> $rows` shape `downloadCsv()` already receives, so **no query logic is duplicated** — only the output format differs (CSV via `fputcsv`, XLSX via whichever library is approved).

**Recommendation:** option 2. `AccountingReportController::downloadCsv()` already isolates "headers + rows → response" — write a parallel `downloadXlsx(string $filename, array $headers, array $rows)` using the approved library, called from a `format=xlsx` query param or a twin route, so the 6 report methods gain one extra line each rather than duplicated query logic.

**Build order note:** correctly last/parallel — fully decoupled from A/B/C/E; only blocked on the pending dependency approval (`maatwebsite/excel` vs `openspout/openspout` — per CLAUDE.md, must not install without explicit user approval first).

## Build Order Validation

Decided order: **A → C → B → E → D**. Verified against the actual dependency graph found in the code:

| Phase | Depends on (technical) | Blocks |
|-------|--------------------------|--------|
| A (Quotation) | `PostIncomeVoucher` (existing, untouched) | Nothing |
| C (ReteICA) | Nothing (builds its own municipality lookup) | Nothing |
| B (Bank CSV) | `Payment.reconciled_at`, `BankReconciliation` (existing, untouched) | Nothing |
| E (Hook) | Nothing (new model, no coupling) | Nothing |
| D (Excel) | `AccountingReportController::downloadCsv()` row-building code (existing, untouched) — plus **dependency approval** | Nothing |

**No technical blocker found that would change the decided order.** All 5 features touch disjoint parts of the schema/services (Quotation is new tables; ReteICA extends `WithholdingRule`; Bank CSV adds new staging tables read against existing `Payment`/`BankReconciliation`; the invoicing hook is a new 1:1 table; Excel only touches presentation-layer export actions). The only real sequencing constraint is non-technical: Fase D cannot start implementation (only the library evaluation can) until the user approves a new Composer dependency, so it is correctly placed last/parallelizable.

One adjustment worth flagging to the user: the roadmap describes Fase C as "bajo esfuerzo, reusa patrón existente" — given the municipality catalog does not actually exist yet (see Critical Finding above), Fase C's effort estimate should be revised upward slightly to include building and seeding that lookup. This doesn't justify reordering, just re-scoping the phase's task list before starting it.

## Anti-Patterns to Avoid

### Anti-Pattern 1: Reusing `ArchiveMasterPreviewImporter`'s transaction shape for Fase B

**What people might do:** wrap the whole CSV import + matching flow in one `DB::transaction` with dry-run/commit like the JSON importer, assuming "same pattern."
**Why it's wrong:** matching against `Payment` is an interactive, multi-session workflow — a transaction can't stay open across user confirmation clicks over multiple page loads.
**Do this instead:** persist parsed lines immediately (small transaction for the batch insert only), then a separate, idempotent service call per line to confirm a match and set `reconciled_at`.

### Anti-Pattern 2: Embedding ICA rate directly in a `municipalities` lookup table

**What people might do:** put an `ica_rate` column on the new municipality catalog, since "it's per municipality."
**Why it's wrong:** rates change by ordinance over time and may vary by activity/concept, which is exactly what `WithholdingRule`'s existing `starts_on`/`ends_on`/`rate`/`minimum_base` versioning already models. Duplicating that versioning inside a geography lookup creates two sources of truth for the same concern.
**Do this instead:** municipality lookup is pure geography (code + name); rate/validity lives in `WithholdingRule` rows scoped by `dane_municipality_code`, exactly like today's national rules are scoped by date validity.

### Anti-Pattern 3: Growing `IncomeRecord` with unrelated concerns

**What people might do:** add quotation-linkage columns, external-invoice columns, and any future integration fields all directly onto `IncomeRecord` since "that's where income data lives."
**Why it's wrong:** `IncomeRecord` is created once by `PostIncomeVoucher` as an immutable operational detail row (mirrors `Voucher` immutability). Turning it into a growing, sometimes-mutable catch-all breaks that invariant and makes it unclear which fields are set-once-at-posting vs. editable-later.
**Do this instead:** keep `IncomeRecord` as-is; add related 1:1/1:N tables (`ExternalInvoiceReference`, `Quotation` back-reference) for anything captured outside the original posting flow.

## Sources

- Direct code inspection (HIGH confidence, no training-data assumptions): `app/Models/Company.php`, `app/Models/Dependency.php`, `app/Models/CompanySignatory.php`, `app/Models/ThirdParty.php`, `app/Models/WithholdingRule.php`, `app/Models/Payment.php`, `app/Models/IncomeRecord.php`, `app/Services/Accounting/ApplyWithholdingRules.php`, `app/Services/Accounting/PostIncomeVoucher.php`, `app/Services/Accounting/BankReconciliation.php`, `app/Services/Accounting/BuildVoucherNumber.php`, `app/Services/Warehouse/BuildWarehouseMovementNumber.php`, `app/Services/Imports/ArchiveMasterPreviewImporter.php`, `app/Http/Controllers/AccountingReportController.php`, `app/Filament/Pages/AccountsPayableReport.php`, `database/migrations/2026_07_30_120050_add_dane_codes_to_companies_table.php`, `composer.json`.
- `.planning/codebase/ARCHITECTURE.md`, `.planning/codebase/STRUCTURE.md`, `.planning/PROJECT.md`, `docs/roadmap-apolo.md` (project-provided context, read per task instructions).

---
*Architecture research for: ContPass commercial-improvements milestone (Fases A–E)*
*Researched: 2026-09-16*

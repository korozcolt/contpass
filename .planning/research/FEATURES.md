# Feature Research

**Domain:** Commercial/private-market accounting features for a Colombian double-entry accounting app (ContPass) — quotations, ReteICA, bank reconciliation import, e-invoicing hook, Excel export
**Researched:** 2026-09-16
**Confidence:** MEDIUM (WebSearch verified against multiple LatAm accounting vendors — Siigo, Alegra, World Office, Helisa — and cross-checked with Colombian tax rules; no Context7 library docs existed for the domain-specific tax logic, only for the Excel library)

## Feature Landscape

### Table Stakes (Users Expect These)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Quotation states: Draft → Sent → Accepted/Rejected → Expired | Every commercial quoting tool (Siigo, Alegra, DataCRM) models this lifecycle; a quotation stuck in one state with no "sent" or "expired" tracking feels broken to a salesperson | LOW | State machine on `Quotation.status` enum. "Expired" is typically computed from `valid_until` date, not a user action — a scheduled check or accessor, not a manual transition |
| Quotation → sequential numbering per company | DIAN doesn't regulate quotation numbering (it's not a tax document), but every commercial tool numbers quotations consecutively for traceability and customer reference ("Cotización #00234") | LOW | Reuse `BuildVoucherNumber`/`BuildWarehouseMovementNumber` pattern already in ContPass — per-company sequence, not global |
| Quotation → PDF with company branding, line items, subtotal/tax/total, validity date | Baseline expectation for any B2B quoting flow — this is the artifact salespeople email to the customer | LOW-MEDIUM | Straightforward if ContPass already has a PDF pattern (check existing voucher/report PDFs for reuse) |
| Quotation → Convert to Income/Invoice in one action | The entire point of quoting is to skip re-entering line items when the customer accepts. Manually re-typing lines into a new `IncomeRecord` is a well-known user complaint in accounting software reviews | LOW-MEDIUM | ContPass already plans to reuse `PostIncomeVoucher` — correct approach. Conversion should be a one-way, auditable action (once converted, quotation becomes read-only/linked, not deletable) |
| ReteICA rate lookup by municipality + economic activity (CIIU) | Siigo's own documented flow ("ICA por ciudad") is: register economic activities with their tariff, associate the activity to the third party, and the system pulls the rate from there — **not** a single flat rate per city. This is the actual expected data shape in the Colombian market, more granular than "one rate per municipio" | MEDIUM | This directly affects the open question in `docs/roadmap-apolo.md` ("¿municipio de Company o de ThirdParty?"). Market precedent (Siigo/Siesa) keys the rate by **activity code × municipality**, and the activity is attached to the *ThirdParty* (the provider being paid), not the paying `Company`. A `Company`-only domicile model would under-serve users who transact with third parties across multiple municipalities — flag this as a decision point, not just "manual override" |
| Bank reconciliation import: CSV column mapping UI | Every reconciliation tool (Zoho Books, FreshBooks, dedicated CSV reconcilers) requires the user to map arbitrary bank CSV columns (date, amount, reference, description) to internal fields, because Colombian banks each export CSV differently (Bancolombia, Davivienda, BBVA all differ) | MEDIUM | Cannot assume one fixed CSV schema. At minimum: user selects which column is date/amount/reference on first import, with date-format detection (dd/mm/yyyy vs yyyy-mm-dd) since mixed formats are the #1 reported cause of false mismatches |
| Bank reconciliation: auto-match by amount + date window + reference, with manual fallback | Table stakes in every reconciliation product: exact/fuzzy auto-match for the easy cases, manual pairing UI for the rest — never 100% auto-match, since reference fields are often empty or inconsistent | MEDIUM | ContPass's planned approach (amount + date ± tolerance + reference) matches market pattern exactly. Do NOT rely on reference being present — it must be optional in the matching logic, with amount+date as the primary key and reference as a tiebreaker among same-amount/same-day candidates |
| Bank reconciliation: handle duplicate-amount collisions | When two payments have the same amount on the same day (common with recurring invoices), naive amount+date matching creates false positives | MEDIUM | Disambiguate using third-party name/reference before falling back to "needs manual review" — surface these as an explicit ambiguous-match bucket in the confirmation UI, not silently auto-matched |
| External invoice reference: number, CUFE, provider name, document URL/PDF | Minimum fields needed to reference a DIAN-validated invoice issued by a third-party e-invoicing provider (Siigo Facturación, Alegra, Factus) without ContPass emitting one itself | LOW | CUFE is a 96-character alphanumeric string generated by the DIAN algorithm from issuer NIT + invoice number + date/time + total + taxes + technical key — ContPass only stores it as an opaque string, never generates or validates it. This is correctly scoped as capture-only in the roadmap |
| Excel export: currency and date cells as native Excel types, not text | The single most common Excel-export complaint in accounting tools is numbers/dates exported as text strings, breaking SUM()/pivot tables downstream. Native `NumberFormat` cell styling (currency, date) is baseline, not a differentiator | LOW-MEDIUM | `maatwebsite/excel` v4.0.3 (confirmed compatible with Laravel 13 / PHP ^8.3, released 2026-09-14) supports `WithColumnFormatting` for exactly this — currency and date format codes applied per column |

### Differentiators (Competitive Advantage)

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Quotation validity auto-expiration with visual/status flag | Most competitors track "vencida" but few proactively surface it (e.g., dashboard badge for quotations expiring in 3 days) — small UX touch that reinforces ContPass's traceability/audit identity | LOW | Aligns with Core Value (auditability) more than a generic CRM feature would — worth doing since it's cheap |
| ReteICA activity/municipality catalog reused for reporting (which cities generate the most retention volume) | None of the mainstream privado competitors surface ICA-by-municipality analytics — most just calculate and let the user export for filing | MEDIUM | Only worth it if Fase C's data model captures activity × municipality granularity (see table-stakes note above) — a natural byproduct, not extra work, if the model is done right |
| Bank reconciliation: bulk-confirm a batch of clearly-matched entries with one click | Reduces the manual click count when 80% of a CSV auto-matches — a UX efficiency layer, not core logic | LOW | Cheap value-add once the matching table exists; explicitly still human-confirmed, not silent auto-posting |
| Multi-sheet Excel export (one sheet per report section, e.g., "Balance" + "Detalle por cuenta") | World Office and Helisa tend to export flatter single-sheet CSullos-like output; a well-organized multi-sheet workbook with a summary tab is a small polish differentiator for accountants who live in Excel | LOW-MEDIUM | Only apply to reports that have a natural summary+detail split (e.g., Libro Mayor: summary sheet + per-account detail sheet). Don't force multi-sheet on single-table reports like Balance de Comprobación |
| Quotation line items reusing catalog of frequent products/services (if ContPass has one) | Speeds up quote creation for repeat business — common Alegra/Siigo pattern | LOW (if catalog exists) / MEDIUM (if not) | Check whether ContPass already has an item/service catalog from Almacén (`WarehouseItem`) that could be reused for non-inventory service lines — avoid building a parallel catalog |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|------------------|-------------|
| Automatic e-invoicing emission (DIAN/CUFE generation) inside ContPass | Feels like the "obvious" next step once the external-invoice hook exists | Already explicitly out of scope per PROJECT.md — requires PTO (Proveedor Tecnológico Autorizado) certification, a large regulatory undertaking or ongoing dependency on a certified provider's API, not just a data model | Fase E hook (capture-only reference fields) is the correct scope; active API/webhook integration is deferred to a future milestone once a specific provider is chosen |
| OFX import / live bank feed (Open Banking) for reconciliation | "Why not support all formats, or connect directly to the bank?" | OFX parsing and live bank API integration are materially more complex (auth flows, rate limits, format edge cases) than CSV parsing, and explicitly excluded from Fase B scope | CSV-only for this phase, as already decided; OFX/live feed is a clean candidate for a later phase if a customer specifically needs it |
| AI-assisted reconciliation (auto-guessing ambiguous matches with an LLM) | Common marketing pitch among smaller reconciliation SaaS tools ("bankreconciler.app" and similar) | Non-deterministic matching in an *audit-critical, immutable-ledger* product directly conflicts with ContPass's Core Value (traceability/auditability over convenience) — a wrong auto-match with no clear "why" is worse here than in a generic bookkeeping tool | Deterministic rule-based matching (amount + date window + reference) with mandatory human confirmation for anything ambiguous, exactly as scoped in Fase B |
| ReteICA declaration/filing generation (formularios de declaración municipal) | Once the retention is calculated, it's a natural-feeling next step to also generate the filing document | Filing formats vary by municipality (no unified national standard like DIAN's own filings), turning this into an open-ended, high-maintenance catalog of municipal forms for uncertain commercial payoff | Fase C stops at calculation + accounting registration of the retention, as already scoped; filing stays a manual/external accountant task |
| Full CRM (pipeline stages, follow-up reminders, email/WhatsApp automation) built around quotations | "Since we have quotations, why not add a sales pipeline?" | Turns a lightweight commercial-document feature into a parallel CRM product, diluting focus and competing head-on with dedicated CRM tools (DataCRM, HubSpot) where ContPass has no differentiation | Keep Quotation scoped to document lifecycle + conversion-to-income, as already defined; no pipeline/stage tracking beyond the 4 quotation states |
| Multi-currency quotations/invoicing | Occasionally requested by customers with international clients | Adds FX-rate-versioning complexity across quotation, ReteICA base calculation, and Excel export — no evidence this is expected for ContPass's target segment (Colombian pymes billing in COP) | Single-currency (COP) for this milestone; revisit only if a specific customer segment demands it |

## Feature Dependencies

```
Quotation (Fase A)
    └──feeds──> IncomeRecord (existing) via PostIncomeVoucher (existing)
                    └──may reference──> ExternalInvoiceReference (Fase E)
                                            (a converted quotation's resulting IncomeRecord
                                             is where the external invoice number/CUFE gets attached)

ReteICA municipal parametrization (Fase C)
    └──requires──> DANE municipality catalog (existing, reused from Dependency/CompanySignatory)
    └──extends──> WithholdingRule (existing)
    └──informs──> ApplyWithholdingRules (existing service, gains municipal branch)

Bank reconciliation CSV import (Fase B)
    └──requires──> Payment.reconciled_at (existing, from lightweight reconciliation)
    └──requires──> CashAccount (existing) to scope which account a statement belongs to
    └──enhances──> BankReconciliation service (existing, gains import-driven matching)

Excel export (Fase D)
    └──requires──> approved new dependency (maatwebsite/excel v4, confirmed Laravel 13/PHP 8.4 compatible)
    └──wraps──> existing report services (FinancialStatement, AccountsReceivable, AccountsPayable, etc.)
    └──independent of──> Fases A, B, C, E (can ship in parallel once dependency is approved)

External invoice hook (Fase E)
    └──attaches to──> IncomeRecord (existing) — 1:1 relation or nullable columns
    └──enhanced by──> Quotation conversion (Fase A) — natural point to prompt for external invoice
                       reference once an accepted quotation becomes a real sale
    └──does NOT require──> any of the other four fases (lowest-effort, most independent feature)
```

### Dependency Notes

- **Quotation (Fase A) feeds IncomeRecord via the existing `PostIncomeVoucher` service:** this is the correct reuse — do not build a parallel posting path for quotation-originated income.
- **ReteICA (Fase C) extends `WithholdingRule` and reuses the DANE catalog:** the open design question (Company vs. ThirdParty municipality) has a market-backed answer worth surfacing to the user — mainstream Colombian software (Siigo/Siesa) keys the rate off the **third party's registered economic activity + its municipality**, not the paying company's domicile. Recommend flagging this in requirements definition rather than defaulting to "Company domicile" for simplicity, since it diverges from what accountants using competitor tools will expect.
- **Bank reconciliation (Fase B) enhances rather than replaces** the existing lightweight `BankReconciliation` service and `Payment.reconciled_at` flag — the CSV import adds a matching/staging layer in front of the same confirmation mechanism already built.
- **Excel export (Fase D) has no functional dependency on the other four fases** — it wraps existing (and Fase A/B/C's new) report data. It's blocked only by dependency approval, not by feature sequencing, matching the roadmap's note that it can run in parallel.
- **External invoice hook (Fase E) is enhanced by, but does not require, Quotation (Fase A):** the hook's fields live on `IncomeRecord` regardless of whether that record originated from a converted quotation or was entered directly — but a converted quotation is the natural UX moment to prompt the user for the external invoice reference, since that's when a "sale" becomes real.

## MVP Definition

### Launch With (v1) — matches the already-scoped Fases A–E

- [ ] Quotation: Draft/Sent/Accepted/Rejected/Expired states, sequential numbering, PDF, convert-to-income — essential; this is the highest-perceived-value gap vs. competitors per the roadmap's market research
- [ ] ReteICA: rate parametrization by municipality (with the activity-code granularity question resolved before building) — essential; closes a compliance gap that currently blocks selling to any customer with municipal tax exposure
- [ ] Bank reconciliation: CSV import + column mapping + amount/date/reference auto-match + manual confirmation UI — essential; addresses the top daily-friction pain point (manual reconciliation) named in the market research
- [ ] External invoice hook: nullable reference fields on `IncomeRecord` (number, CUFE, provider, URL) — essential and low-cost; unblocks the "no native e-invoicing" objection during sales conversations
- [ ] Excel export: single-sheet exports with native currency/date formatting for the existing report set — essential once the dependency is approved; matches competitor baseline

### Add After Validation (v1.x)

- [ ] Quotation expiration dashboard flag/badge — add once quotations are in real use and stale ones start accumulating
- [ ] Bulk-confirm for reconciliation batches — add once real bank CSVs reveal how much of each import auto-matches cleanly
- [ ] Multi-sheet Excel export for reports with a natural summary+detail split (Libro Mayor) — add after single-sheet export ships and users request more structure

### Future Consideration (v2+)

- [ ] ReteICA-by-municipality analytics/reporting — defer until the underlying data model has been in production long enough to have meaningful volume
- [ ] Reusable product/service catalog for quotation line items — defer unless quotation usage shows repeat-item friction; don't build speculatively
- [ ] Active e-invoicing provider API integration (beyond the data hook) — explicitly deferred to a future milestone per PROJECT.md, pending provider selection

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Quotation lifecycle + conversion (Fase A) | HIGH | MEDIUM | P1 |
| ReteICA municipal parametrization (Fase C) | HIGH | MEDIUM | P1 |
| Bank reconciliation CSV import + matching (Fase B) | HIGH | MEDIUM-HIGH | P1 |
| External invoice reference hook (Fase E) | MEDIUM | LOW | P1 |
| Excel export with native formatting (Fase D) | MEDIUM | LOW-MEDIUM (pending dependency approval) | P1 |
| Quotation expiration dashboard flag | LOW-MEDIUM | LOW | P2 |
| Reconciliation bulk-confirm | MEDIUM | LOW | P2 |
| Multi-sheet Excel export | LOW-MEDIUM | LOW-MEDIUM | P2 |
| ReteICA municipality analytics | LOW | MEDIUM | P3 |
| Reusable quotation item catalog | LOW-MEDIUM | MEDIUM | P3 |

**Priority key:**
- P1: Must have for this milestone (already scoped as Fases A–E)
- P2: Should have, add once the P1 phases are in production and generating usage data
- P3: Nice to have, future consideration — do not build speculatively

## Competitor Feature Analysis

| Feature | Siigo | Alegra / World Office / Helisa | ContPass's Approach |
|---------|-------|-------------------------------|----------------------|
| Quotations | Full CRM-adjacent quoting with follow-up tracking | Similar; quotations convert to invoice in one click | Lighter scope: 4-state lifecycle + conversion only, no CRM/pipeline layer — matches Core Value focus on control/traceability, not sales automation |
| ReteICA | Rate keyed by economic activity registered on the third party, associated at the activity level, applied per municipality | Similar activity-based parametrization (Siesa docs confirm same pattern) | Should follow the same activity × municipality keying rather than a flatter Company-domicile-only model, per market precedent found above |
| Bank reconciliation | Manual + rule-based matching, no AI features publicized | Similar; some smaller players in reconciliation-as-a-service pitch "AI-assisted" matching | Deterministic rule-based matching only, explicitly excluding AI — aligns with the audit-first Core Value, not a gap vs. competitors that matters to this product's buyers |
| E-invoicing | Native, PTO-certified emission | Native, PTO-certified emission | Reference-only hook, no native emission — the one area where ContPass is behind, by design, offset by lower price point and partnering with a third-party PTO |
| Excel export | Available in most paid tiers | Available in most paid tiers | Matches baseline; native cell formatting is the bar to clear, not exceed |

## Sources

- [Siigo — ICA por ciudad (documented parametrization flow: activity + municipality tariff, associated to third party)](https://siigopyme.portaldeclientes.siigo.com/basedeconocimiento/ica-por-ciudad/)
- [Siesa — Guía ReteICA Colombia](https://www.siesa.com/blog/guia-reteica-colombia/)
- [Siesa — ReteICA qué es y a quiénes se les aplica](https://www.siesa.com/blog/reteica-que-es-y-a-quienes-se-les-aplica/)
- [Siigo — ¿Qué es ReteICA y cuándo se aplica?](https://www.siigo.com/blog/que-es-reteica-y-cuando-se-aplica/)
- [SysCafe docs — Retención de Industria y Comercio (ICA)](https://doc.syscafe.com/post/industria-y-comercio-ica)
- [Gestio blog — Bank reconciliation CSV import mapping rules that prevent bad matches](https://www.gestio.dev/blog/bank-reconciliation-csv-import-mapping)
- [bankreconciler.app — How Automated Bank Reconciliation Works: Matching Logic Explained](https://bankreconciler.app/blogWhatIsReconcileIQ)
- [bankreconciler.app — How to Match Invoices to Bank Payments Automatically](https://bankreconciler.app/blogInvoicePaymentMatching)
- [siemprealdia.co — ¿Qué es el CUFE y cómo validar facturas en la DIAN?](https://siemprealdia.co/colombia/impuestos/que-es-el-cufe/)
- [DIAN — Anexo técnico de la factura electrónica de venta v1.9](https://www.dian.gov.co/impuestos/factura-electronica/Documents/Anexo-Tecnico-Factura-Electronica-de-Venta-vr-1-9.pdf)
- [Laravel Excel — Formatting columns (WithColumnFormatting)](https://docs.laravel-excel.com/3.1/exports/column-formatting.html)
- [Packagist — maatwebsite/excel (v4.0.3, Laravel 12/13 + PHP ^8.3 support confirmed)](https://packagist.org/packages/maatwebsite/excel)
- Existing project docs: `/Volumes/NAS(MAC)/Data/Herd/contpass/.planning/PROJECT.md`, `/Volumes/NAS(MAC)/Data/Herd/contpass/docs/roadmap-apolo.md` (already-decided scope for Fases A–E, not re-litigated here)

---
*Feature research for: ContPass commercial-improvements milestone (private-market accounting features)*
*Researched: 2026-09-16*

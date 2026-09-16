# Codebase Concerns

**Analysis Date:** 2026-09-16

## Tech Debt

**Authorization/Policy Layer Missing:**
- Issue: No Filament policies or Laravel authorization gates implemented. Access control relies on inline role checks in Filament resources (e.g., checking `auth()->user()->role === 'admin'`) and basic `abort_unless()` in web routes. No granular resource-level authorization.
- Files: `app/Filament/Resources/**`, `routes/web.php` (all 62 route files lack policy middleware), no `app/Policies/` directory exists
- Impact: Cannot implement granular per-resource permissions (e.g., "user can view accounting reports but not edit payments"). Multi-tenant separation by `company_id` is enforced only at model query level, not at request/policy boundary — data from one company could theoretically leak if a query is misconfigured. Risk increases if features scale to true multi-tenancy with user-per-company separation.
- Fix approach: Implement Filament authorization policies (`app/Policies/`) following Laravel conventions. Add `authorize()` calls in form save/delete actions. Use middleware or policy checks to scope queries to current company context. Document "who can do what" as formal gate definitions.

**Test Coverage Fragmentation:**
- Issue: 25 test files for 288 source files (~8.7% coverage). Critical untested models: `BudgetObligation`, `Payment`, `ExpenseRecord`, `AccountingEntry`, `CashAccount`, `PaymentOrder`, `BudgetAppropriation`, `BudgetRegistration`. These are core domain entities in the accounting system, yet no unit or feature tests exist.
- Files: `tests/Feature/` (25 test files), missing: `BudgetObligation`, `Payment`, `ExpenseRecord`, `CashAccount`, `PaymentOrder`, `BudgetAppropriation`, `BudgetRegistration`, `ChartAccount`, `AccountingPeriod`, `Warehouse`, `WarehouseItem`
- Impact: Changes to payment logic, budget obligation handling, or accounting entries could introduce bugs undetected. Regression testing is manual for ~10 critical models. Factory definitions exist for all models, but tests don't exercise them.
- Fix approach: Add feature tests for payment registration flow, budget obligation lifecycle, and expense/income voucher creation. Test models in isolation where behavior is non-trivial (e.g., `Payment.isReconciled` accessor, `Voucher.isBalanced()`, `CashProgramItem.executed_amount`). Prioritize the top 5 untested models by frequency of business use.

**Single Data Seeder:**
- Issue: Only `DatabaseSeeder.php` exists. 32 factories defined, but no secondary seeders for different environment scenarios (test data, development fixtures, demo accounts). Seeding for local development requires knowing all factory state/relationships.
- Files: `database/seeders/DatabaseSeeder.php` (single file), `database/factories/` (32 factories unused outside tests)
- Impact: Onboarding developers requires manual creation of test data; local instances may diverge in test data layout. Demo/QA environments lack consistent setup.
- Fix approach: Create `seeders/DemoDataSeeder.php` for full-featured demo with accounts, employees, budgets, payments. Create `seeders/MinimalSeeder.php` for fast test runs. Document in `README.md` which seeder to use per environment. Update `composer setup` to run the appropriate seeder.

**Deprecated Blade Routes Not Removed:**
- Issue: Old `/login`, legacy CRUD Blade templates for incomes, expenses, payments still exist in `resources/views/` but are superseded by Filament at `/admin`. Routes and views are marked deprecated in `docs/contpass-context.md` but not removed from codebase. Creates maintenance confusion (which views are live?).
- Files: `resources/views/` (legacy templates), `routes/web.php` (no legacy route definitions, but old views remain)
- Impact: Developers may accidentally edit wrong template. Dead code adds noise to filesystem. If legacy routes are re-added in future, git history will show multiple adds/removes of same code.
- Fix approach: Delete all non-Filament Blade templates from `resources/views/`. Confirm no routes point to them (search for `view('...')` calls in controllers). Commit as cleanup: "refactor: remove deprecated Blade templates, migrate fully to Filament v5".

**Authorization in Routes vs. Middleware:**
- Issue: Auth checks inline in route closures: `abort_unless($request->user() !== null, 403)`. No middleware guard or policy check. If more granular checks are needed (e.g., "user must be accountant"), the check must be duplicated across 10+ route definitions or added to the controller method.
- Files: `routes/web.php` (60+ line routes with repeated auth checks)
- Impact: Inconsistent auth logic; hard to audit all protected routes in one place. Adding a new permission type requires editing multiple route definitions.
- Fix approach: Create middleware `app/Http/Middleware/EnsureUserIsAuthenticated.php` or leverage Filament's built-in auth via proper resource/page definition. Consolidate auth checks into a single place per route group.

## Known Bugs

**Filament v5 Enum Select Bug (Issue #1) — FIXED BUT ISSUE OPEN:**
- Symptom: `Select` fields with `->options(EnumClass::class)` using `$get()` in `visible()`/`required()` closures always evaluated to false because `$get()` returns the enum instance, not the `->value`. Comparisons like `$get('type') === Enum::Case->value` always failed.
- Files: `app/Filament/Resources/WarehouseMovements/Schemas/WarehouseMovementForm.php` (3 affected fields), `app/Filament/Resources/BudgetAppropriations/Schemas/BudgetModificationsForm.php`, `app/Filament/Resources/BudgetAppropriations/RelationManagers/BudgetModificationsRelationManager.php` (fixed by comparing to `Enum::Case` directly)
- Current state: Fixed in code (2026-07-30) by comparing `$get('type') === Enum::Case` instead of `Enum::Case->value`. Tests pass. **GitHub issue #1 remains open** — should be closed.
- Impact if reverted: Conditional field visibility in warehouse movements (warehouse destination, dependency destination, supplier) would fail silently; forms would appear incomplete to users.

**PaymentOrderFactory Incorrect Enum (Issue #2) — FIXED BUT ISSUE OPEN:**
- Symptom: Factory assigned `'method' => PaymentMethod::Transfer`, but `PaymentMethod` enum only defines `BankTransfer`, not `Transfer`. Any test using `PaymentOrder::factory()` without overriding `method` failed with "Undefined constant" error.
- Files: `database/factories/PaymentOrderFactory.php` (fixed to use `PaymentMethod::BankTransfer`)
- Current state: Fixed in code (2026-07-30). Tests like `CashProgramItemTest` that depend on `PaymentOrder::factory()` now pass. **GitHub issue #2 remains open** — should be closed.
- Impact: Factory was unusable, blocking any test that relied on `PaymentOrder::factory()` without manual method override. Now fixed.

**Action:** Close GitHub issues #1 and #2 with commit references. These are fixed and should not block development.

## Security Considerations

**Multi-Tenant Data Isolation Weak:**
- Risk: Company data is scoped by `company_id` filter in model queries, but no authorization policy enforces it. If a developer writes `Payment::all()` instead of `Payment::whereBelongsTo($company)`, data leaks across companies undetected. No policy-level gating means wrong company can be passed to a service and succeed silently (e.g., `RegisterPayment::handle($otherCompany, ...)`).
- Files: `app/Models/Company.php` (scoping relationship), `app/Services/Accounting/**` (all services accept `Company $company` parameter, but Filament forms may pass wrong company), `app/Filament/Resources/**` (no explicit policy validation)
- Current mitigation: Filament forms hardcode current company via `CurrentCompany` service. If a user is ever allowed multi-company access, this breaks.
- Recommendations: Implement a `companyId()` method on User model to enforce current company. Add policy `canManagePayment(User $user, Payment $payment)` that checks `$user->company_id === $payment->voucher->company_id`. Add test case where user from Company A tries to access Payment from Company B (should fail).

**Audit Logging Coverage Unclear:**
- Risk: `AuditLog` model exists (via `Auditable` trait), but not clear which operations are logged. If sensitive operations (payment approval, budget modification, withholding rule changes) are not audited, compliance gaps arise (Colombian law 1314 requires audit trails for financial changes).
- Files: `app/Models/Voucher.php`, `app/Models/Payment.php` (use `Auditable` trait), `app/Traits/Auditable.php` (unclear which events trigger logging)
- Current mitigation: Assuming default Eloquent events (create, update, delete) are logged via the trait.
- Recommendations: Document exactly which operations are logged (create voucher, modify voucher, delete payment, etc.). Verify that all create/delete operations on financial models trigger the audit. Test that audit log entries are immutable (no update/delete). Consider adding "who performed this action" context (currently missing if user is in `Auditable`).

**No Rate Limiting on Report Exports:**
- Risk: CSV export endpoints (`accounting-reports/ledger`, etc.) have no rate limiting. Malicious actor could request thousands of exports, overloading server/memory.
- Files: `routes/web.php` (export endpoints), `app/Http/Controllers/AccountingReportController.php` (downloadCsv method)
- Current mitigation: Only authenticated users can access. No per-IP/per-user throttle.
- Recommendations: Add `throttle:60,1` middleware to report export routes. Consider caching large reports (balance sheet, ledger) for 1 minute if requested by same user within window.

## Performance Bottlenecks

**Large File Importer (ArchiveMasterPreviewImporter — 431 lines):**
- Problem: Single service method `import()` does everything: validation, upsert company/accounts/third parties/periods, iterate vouchers, decide import/skip/reject. No streaming; entire JSON file must be loaded into memory. For large migrations (e.g., 50k+ vouchers), could exhaust memory.
- Files: `app/Services/Imports/ArchiveMasterPreviewImporter.php` (431 lines with deeply nested logic)
- Cause: Single responsibility violated; method handles schema mapping, voucher iteration, error collection. No chunking or streaming parser.
- Improvement path: Refactor into separate classes: `ArchiveSchemaMapper`, `ArchiveVoucherImporter`, `ArchiveImportSummary`. Use streaming JSON parser (e.g., `JSON_PARSER_STREAMING` or line-delimited JSON) for large files. Add batch insert for vouchers (e.g., 1000 at a time) instead of one-by-one iteration.

**FinancialStatement Service Query Patterns:**
- Problem: `generalLedger()` method merges opening + period balances by collecting account codes, then maps over them. No query index hints. If company has 10k+ accounts, the collection merge could be slow.
- Files: `app/Services/Accounting/FinancialStatement.php` (lines 75-107)
- Cause: Report needs opening balance from previous period + current period entries, so queries run separately then merged in PHP. Could be optimized with a single window function query (if using PostgreSQL).
- Improvement path: Use PostgreSQL window functions (`LAG()`, `SUM() OVER(PARTITION BY account_id ORDER BY date)`) to compute opening/period/closing balances in one query. Cache reports with `remember()` if they're requested multiple times (e.g., dashboard + export same report).

**No Pagination Consistency in Reports:**
- Problem: Some reports paginate (e.g., `LedgerReport` paginates 50 rows), others render full results in memory (e.g., `TrialBalanceReport` collects all rows, no pagination). For 100k+ entries, in-memory reports could slow down.
- Files: `app/Http/Controllers/AccountingReportController.php` (ledger paginates, trial balance doesn't), `app/Filament/Pages/**Report.php` (inconsistent pagination)
- Cause: Each report built independently without pagination standard.
- Improvement path: Establish pagination policy: reports with >100 rows must paginate. Add `withQueryString()` to preserve filters across pagination. Cache report page results (5 min TTL) for CSV exports to avoid double-query.

## Fragile Areas

**Conditional Field Visibility in Filament Forms:**
- Files: `app/Filament/Resources/WarehouseMovements/Schemas/WarehouseMovementForm.php`, `app/Filament/Resources/BudgetAppropriations/Schemas/BudgetModificationsForm.php`, `app/Filament/Resources/BudgetAppropriations/RelationManagers/BudgetModificationsRelationManager.php`
- Why fragile: Form field visibility depends on enum comparisons via `$get()`. After fix for issue #1, code compares directly to enum cases (e.g., `$get('type') === MovementType::Outbound`). If enum case names change or are removed, forms silently hide fields instead of erroring. Test coverage is implicit in form render tests, not explicit.
- Safe modification: When refactoring movement types or modification types, update form visibility checks in lock-step. Add unit test for each enum case: "field X is visible when type = Y". Document enum case names in form comments.

**Budget Modification Chain (Appropriations → Registrations → Obligations):**
- Files: `app/Models/BudgetModification.php` (115 lines), `app/Filament/Resources/BudgetAppropriations/Schemas/BudgetModificationsForm.php`, `app/Filament/Resources/BudgetAppropriations/RelationManagers/BudgetModificationsRelationManager.php` (129 lines)
- Why fragile: Modifying a budget appropriation cascades to registrations and obligations. If a modification is deleted mid-flow (form save fails after partial update), database could be left inconsistent. No explicit transaction wrapping in the Filament resource, relying on Laravel's implicit transaction per save.
- Safe modification: Wrap the entire modification flow (appropriation update + child cascade) in explicit `DB::transaction()` in a dedicated service. Test rollback scenario: attempt to modify with invalid child data, verify nothing persists.

**Warehousing Stock Calculation (WarehouseStockReport):**
- Files: `app/Filament/Pages/WarehouseStockReport.php` (109 lines), `app/Models/WarehouseMovement.php` and `WarehouseMovementLine.php`
- Why fragile: Stock balance = opening + inflows − outflows. If a movement is deleted or reversed, calculation must account for it. Currently, calculations are done in PHP queries, not in the database (no materialized view or trigger). If data is corrupted (e.g., a line quantity is wrong), stock report will be wrong permanently.
- Safe modification: Add a `stock_balance` column or separate `WarehouseStockBalance` table, updated on each movement insert/delete via observer. Validate that stock never goes negative (constraint or check). Test edge cases: delete middle movement, verify stock recalculates. Document the stock calculation method in a comment.

**Cash Program Item Executed Amount Calculation:**
- Files: `app/Models/CashProgramItem.php` (104 lines, accessor `executed_amount`), related to `BudgetObligation`, `Payment`, `IncomeRecord`
- Why fragile: `executed_amount` sums payments for a budget item across a month. If a payment is posted to the wrong budget item or month, the amount will be misattributed. No validation that a payment's date matches the calendar month of its budget item. Test exists (`CashProgramItemTest`), but only tests happy path (correct payment linked to correct item).
- Safe modification: Add validation in `RegisterPayment` service: ensure payment month matches the `CashProgramItem` month. Add negative test: attempt to post payment in March to a February `CashProgramItem`, verify failure. Document the month-matching rule.

## Scaling Limits

**No Database Connection Pooling Configuration:**
- Concern: PostgreSQL default connection limit is 100. `redis` is configured for sessions, but no mention of connection pooling for database reads. Under high concurrent load (e.g., 50+ simultaneous report exports + normal CRUD), could hit connection limit and start rejecting requests.
- Current setup: `config/database.php` uses standard Laravel PostgreSQL driver, no PgBouncer or explicit pool config.
- Scaling path: If concurrent users exceed 30, evaluate PgBouncer (connection multiplexer) or RDS proxy. Configure `DB_POOL_SIZE` and `DB_POOL_TIMEOUT` in `.env`. Monitor with `SELECT count(*) FROM pg_stat_activity;`.

**No Caching for Accounting Reports:**
- Concern: Reports like `BalanceSheet` and `IncomeStatement` are computed fresh on each request. If run multiple times in one day, same expensive queries repeat. No query result cache.
- Current setup: No Redis cache layer for reports.
- Scaling path: Add caching in `FinancialStatement` service: `Cache::remember('balance_sheet_'.$company->id.'_'.date('Y-m-d'), 300, fn() => ...)` (5 min TTL). Invalidate cache on posting new voucher. Document in comments which reports are cached vs. live.

**Audit Log Growth Unbounded:**
- Concern: `AuditLog` table has no retention policy. Over years, audit logs could grow to millions of rows, slowing queries that join on voucher.id.
- Files: `app/Models/AuditLog.php` (no pruning defined), `database/migrations/` (AuditLog table setup unclear if it has indexes)
- Scaling path: Add artisan command `audit:prune --days=365` (delete logs older than 1 year). Schedule to run monthly via `app/Console/Kernel.php`. Add index on `auditable_type` and `created_at` for query speed. Document retention policy in `docs/contpass-context.md`.

**Warehouse Movement Lines Not Indexed:**
- Concern: `WarehouseMovementLine` table has no explicit index on `warehouse_movement_id`. Stock reports join through this table; if movements have 100+ lines and company has 1000+ movements, stock calculation could be slow.
- Files: `database/migrations/2026_07_30_124604_create_warehouse_movement_lines_table.php` (no visible indexes beyond FK)
- Scaling path: Add index on `(warehouse_movement_id, created_at)` in migration. Re-run stock report performance test with 10k movements.

## Dependencies at Risk

**No Excel Export Library:**
- Risk: Roadmap (Fase D: Exportación Excel dedicada) requires adding Excel export. Currently, only CSV export is supported. Adding `maatwebsite/excel` or `openspout/openspout` is explicitly pending user approval per guidelines.
- Impact: Excel export is listed as commercial differentiator vs. competitors (Alegra, Siigo) but is blocked on dependency decision.
- Migration plan: Once user approves, install `openspout/openspout` (lighter than maatwebsite/excel, no PHPOffice dependency). Wrap export logic in `app/Services/Export/ExcelExporter.php`. Update all report pages to include Excel action button alongside CSV.

**Predis/Redis Hard Requirement:**
- Risk: `config/cache.php` and `config/session.php` default to Redis. If Redis becomes unavailable, sessions and caching fail (not graceful fallback to file/database).
- Impact: Production downtime if Redis server is down; development setup requires running Redis.
- Mitigation: `composer.json` pins `predis/predis": "^3.4"`. Change `CACHE_STORE` to `file` and `SESSION_DRIVER` to `database` in `.env.example` for users without Redis. Document that Redis is optional but recommended for production.

**Filament v5 Migration Risk:**
- Risk: Filament 5.6 is recent (released 2026-Q2). Breaking changes in minor versions could occur. No custom plugins pinned to specific versions.
- Impact: `composer update` could introduce breaking changes. Future Filament upgrades may require form refactoring.
- Mitigation: Run tests after `composer update` before deploying. Document all custom form components in `CONVENTIONS.md` so future maintainers know what's at risk. Subscribe to Filament release notes.

## Missing Critical Features

**Nómina Calculation Engine Missing:**
- Issue: Phase 3b (Nómina) completed with master data only: `Employee`, `PayrollFund`, `PayrollConcept`. No calculation engine for liquidation, aportes (salud/pensión/ARL), cesantías, prima, liquidación definitiva.
- Files: `app/Models/Employee.php`, `app/Models/PayrollFund.php`, `app/Models/PayrollConcept.php` (21 of 25 payroll functions blocked per roadmap)
- Impact: System cannot process any payroll; all payroll operations are manual. Roadmap explicitly marks this as blocked until user confirms tax rates/formulas.
- Status: Explicitly out of scope pending user confirmation. Documented in `docs/roadmap-apolo.md` ("Explícitamente fuera: cualquier cálculo").

**Fixed Asset Depreciation Model Missing:**
- Issue: Audit vs. Apolo identified "Depreciación de activos fijos" as missing. No `Asset`, `AssetDepreciation`, or depreciation schedule.
- Files: Not present in codebase.
- Impact: Companies cannot track property, plant, equipment or calculate depreciation expense. Balance sheet asset values may be incorrect.
- Status: Listed as pending in roadmap, no planned phase yet.

**Secretaría (Contratación) Module Missing:**
- Issue: Procurement/contracting module is 11 functions in Apolo, zero coverage in ContPass.
- Files: Not present in codebase.
- Impact: System handles expenses but not procurement process (RFQ, PO, vendor management, contract terms).
- Status: Listed as large module requiring "alcance y confirmación de reglas" before coding.

## Test Coverage Gaps

**Untested Model Relationships:**
- What's not tested: Payment → Voucher → ThirdParty chain. BudgetObligation → PaymentOrder relationship. CashAccount balances after payments. IncomeRecord — ExpenseRecord symmetry (both have account pairs).
- Files: `app/Models/Payment.php` (no test for payment.voucher relationship), `app/Models/BudgetObligation.php` (untested), `app/Models/CashAccount.php` (untested), `app/Models/IncomeRecord.php` and `ExpenseRecord.php` (untested)
- Risk: Changes to relationship definitions (e.g., adding `withoutGlobalScopes()`) could break queries silently. Payments could fail to load parent vouchers undetected.
- Priority: High — Add relationship tests for Payment, BudgetObligation, and CashAccount. Test that payment lookup by voucher returns correct results.

**Edge Cases in Accounting Services:**
- What's not tested: `PostExpenseVoucher` with withholdings summing to more than amount (edge case: 100 withholding on 50 amount). `RegisterPayment` with partial payment (pay 50 of 100 obligation, then pay 50 again — verify balance updates). `ApplyWithholdingRules` with rate changes mid-period.
- Files: `app/Services/Accounting/PostExpenseVoucher.php` (no test for overbilling scenario), `RegisterPayment.php` (no test for partial payments), `ApplyWithholdingRules.php` (no test for rate updates)
- Risk: Edge cases could cause incorrect accounting entries or payment balances.
- Priority: Medium — Add edge case tests after core relationship tests are done.

**Filament Form Validation:**
- What's not tested: Required field validation, enum constraint validation, numeric precision (2 decimal places enforced?), date range constraints.
- Files: `app/Filament/Resources/**/Schemas/*.php` (210+ form builder files, no explicit form tests)
- Risk: Forms could accept invalid data, causing database constraint violations or incorrect calculations.
- Priority: Medium — Add Pest form validation tests for 3 critical resources: IncomeRecords, ExpenseRecords, Payments. Test that saving with missing required fields fails. Test that decimal values with >2 places are rounded correctly.

---

*Concerns audit: 2026-09-16*

# Architecture

**Analysis Date:** 2026-09-16

## Pattern Overview

**Overall:** Domain-Driven Design with Service-Based Architecture

ContPass uses a separation of concerns pattern where Filament (presentation layer) delegates all business logic to domain services. This prevents form handlers from directly manipulating the database and ensures all accounting operations pass through validated, reusable service implementations.

**Key Characteristics:**
- Domain services enforce business rules (e.g., voucher balance validation, period state checking, withholding rule application)
- Filament forms are presentation-only; they invoke domain services to persist data
- Immutability by design: approved vouchers cannot be edited; corrections occur through adjustment notes
- Transactions wrap multi-step operations to maintain data consistency
- Enums enforce type safety for statuses, account natures, payment methods, and operational types

## Layers

**Presentation Layer:**
- Purpose: Capture user intent and display data via Filament admin panel
- Location: `app/Filament/Resources`, `app/Filament/Pages`, `app/Filament/Widgets`
- Contains: Resource classes with schemas and tables, custom pages (reports), dashboard widgets
- Depends on: Domain services, models, enums
- Used by: End users via `/admin` panel

**Application Layer:**
- Purpose: Handle HTTP requests and delegate to domain services
- Location: `app/Http/Controllers`, `app/Http/Requests`
- Contains: Controllers for legacy/report endpoints (mostly deprecated), custom form requests
- Depends on: Domain services, models
- Used by: Public API endpoints (`accounting-reports/*`), Filament form creation pages

**Domain Layer (Services):**
- Purpose: Implement business logic and enforce rules
- Location: `app/Services/Accounting`, `app/Services/Budget`, `app/Services/Warehouse`, `app/Services/Imports`
- Contains: Service classes that validate, create, and modify models; import logic
- Depends on: Models, enums, database transactions
- Key services:
  - `PostIncomeVoucher` — create income voucher with receivable and revenue accounts
  - `PostExpenseVoucher` — create expense voucher with withholding calculation
  - `RegisterPayment` — record payment and mark as reconciled if applicable
  - `ApplyWithholdingRules` — calculate and apply withholding tax by rule, basis, rate, validity
  - `CreateAdjustmentVoucher` — create correction note for approved voucher
  - `EnsureOpenAccountingPeriod` — validate that operation date falls within open period
  - `PostsBalancedVoucher` — create balanced voucher with debit/credit entries (called by income/expense services)
  - `BankReconciliation` — calculate pending/reconciled amounts by cash account
  - `FinancialStatement` — generate general ledger, balance sheet, income statement, journal
  - `AccountsReceivable`, `AccountsPayable` — aging analysis by date buckets
  - Budget services: `CreateBudgetObligation`, `ApproveBudgetObligation`, `IssueBudgetCertificate`, `IssuePaymentOrder`, `ExecutePaymentOrder`, `ApplyBudgetRegistration`
  - Warehouse services: `BuildWarehouseMovementNumber`
  - Import service: `ArchiveMasterPreviewImporter` — dry-run and commit JSON master data imports

**Data Layer (Models & Persistence):**
- Purpose: Define schema, relationships, and accessors
- Location: `app/Models`
- Contains: Eloquent models with relationships, casts, and computed properties
- Key models: `Company`, `ThirdParty`, `Voucher`, `AccountingEntry`, `IncomeRecord`, `ExpenseRecord`, `Payment`, `ChartAccount`, `CashAccount`, `WithholdingRule`, `AccountingPeriod`, `BudgetObligation`, `PaymentOrder`, `Warehouse`, `WarehouseItem`, `WarehouseMovement`, `Employee`, `PayrollFund`, `PayrollConcept`, `PettyCashFund`, `PettyCashMovement`, `CashProgramItem`

**Infrastructure Layer:**
- Purpose: Handle cross-cutting concerns and system integrations
- Location: `app/Traits`, `app/Jobs`, `app/Providers`, `config/`, database migrations
- Contains: Audit logging trait, queue jobs, service providers, environment configuration
- Traits: `Auditable` — async audit log dispatch for all create/update events
- Jobs: `ProcessAuditLog` — asynchronous recording of model changes
- Providers: `FilamentPanelProvider` — Filament admin panel configuration and discovery

## Data Flow

**Income Recording Flow:**

1. User navigates to Filament Income Records resource
2. Filament form renders `IncomeRecordResource::form()` with schemas
3. User submits form data (third party, date, account IDs, amount, support number)
4. `CreateIncomeRecord` page handler calls `PostIncomeVoucher::handle()`
5. Service validates period is open via `EnsureOpenAccountingPeriod`
6. Service calls `PostsBalancedVoucher::handle()` with debit (receivable) and credit (revenue) entries
7. Service creates balanced `Voucher` model with auto-approved status
8. Service creates accompanying `IncomeRecord` (operational detail)
9. Transaction commits; audit log dispatched asynchronously
10. Filament redirects to resource list view

**Expense Recording Flow:**

1. Similar entry point but invokes `PostExpenseVoucher::handle()`
2. Service calls `ApplyWithholdingRules::handle()` to calculate withholding taxes
3. Service creates voucher with three entries: expense/cost, withholding, account payable
4. Service creates `ExpenseRecord` with deducibility and support flags

**Payment Recording Flow:**

1. User creates `Payment` via Filament, selecting source voucher (or none), cash account, counterpart, method
2. `CreatePayment` page calls `RegisterPayment::handle()`
3. Service optionally calls `BankReconciliation` logic if method is banked (not cash)
4. Service creates `Payment` model with optional `reconciled_at` timestamp
5. If cash payment, sets `reconciled_at` to null and alerts user via UI

**Adjustment (Correction) Flow:**

1. User identifies approved voucher requiring correction
2. User creates adjustment note via `CreateAdjustmentVoucher`
3. Service creates new voucher of type Adjustment with `adjusts_voucher_id` reference
4. Original voucher marked as Adjusted status
5. Adjustment voucher is immediately approved (immutable like original)
6. Trazability preserved: original → adjustment relationship queryable

**State Management:**

- Vouchers: `Draft → Approved → [Adjusted]` or `[Voided]` (no direct editing of approved)
- Accounting periods: `Open → Closed` (blocks new causations in closed period)
- Budget obligations: `Draft → Committed → Obligated → Paid`
- Payment order: `Draft → Authorized → Executed`
- Cash program items: projections vs. executed actuals (read-only projection, executable actual)

## Key Abstractions

**Domain Services:**
- Purpose: Encapsulate business rules and multi-step operations
- Examples: `PostIncomeVoucher`, `PostExpenseVoucher`, `ApplyWithholdingRules`, `RegisterPayment`, `BankReconciliation`, `FinancialStatement`
- Pattern: Each service is a single-action class (invokable or named `handle()` method); depends on other services for composition

**Enums (Type Safety):**
- `VoucherType` (Income, Expense, Adjustment, BudgetObligation, Payment)
- `VoucherStatus` (Draft, Approved, Adjusted, Voided)
- `VoucherType`, `CashAccountType` (Caja, Banco)
- `AccountNature` (Asset, Liability, Equity, Revenue, Expense, Cost, CostOfSales)
- `PaymentMethod` (BankTransfer, Check, Card, Deposit, Cash, Other)
- `ThirdPartyType` (PersonaNatural, PersonaJuridica)
- `BudgetObligationStatus`, `PaymentOrderStatus`, `BudgetCertificateStatus`
- `CashProgramMovementType` (Income, Expense)
- `PettyCashMovementType` (Opening, Expense, Replenishment, Closure)

**Filament Resources:**
- Purpose: Declarative CRUD interfaces for models; delegate creation/update to domain services
- Pattern: `Resource` class with `form()`, `table()`, `getRelations()`; custom Create/Edit/List pages override default handlers
- Examples: `IncomeRecordResource`, `ExpenseRecordResource`, `PaymentResource`, `VoucherResource`, `ThirdPartyResource`
- Form composition via `app/Filament/Support/AccountingFormFields.php` (reusable field builders)

**Filament Pages (Reports):**
- Purpose: Query-driven views for analysis and monitoring
- Pattern: `Page` class implementing `HasTable`; `table()` method builds query with filters/columns/export
- Examples: `LedgerReport`, `TrialBalanceReport`, `GeneralLedgerReport`, `BalanceSheetReport`, `IncomeStatementReport`, `AccountsReceivableReport`, `AccountsPayableReport`
- Export: CSV download via table action; same logic as controller endpoint

**Models with Rich Relationships:**
- `Voucher` → `entries` (AccountingEntry), `incomeRecord`, `expenseRecord`, `payment`, `adjustsVoucher`, `company`, `thirdParty`
- `IncomeRecord` → `voucher`, `revenueAccount`, `receivableAccount`
- `ExpenseRecord` → `voucher`, `expenseAccount`, `payableAccount`, `budgetObligation`
- `Payment` → `voucher`, `cashAccount`, `paymentOrder`, `company`
- `BudgetObligation` → `budgetAvailabilityCertificate`, `expenseRecord`, `paymentOrder`, `company`

## Entry Points

**Filament Admin Panel:**
- Location: `/admin`
- Triggers: Filament resource actions (create, update, delete, bulk, custom actions)
- Responsibilities: Form submission → domain service invocation → model persistence

**Public Landing:**
- Location: `/`
- View: `resources/views/welcome.blade.php`
- Purpose: Structural overview of architecture, modules, services, and guarantees (no operational data exposed)

**CSV Report Exports:**
- Location: `GET /accounting-reports/{ledger|third-party-movements|trial-balance|journal|financial-statements|accounts-receivable|accounts-payable|general-ledger|bank-reconciliation}`
- Triggers: Authenticated GET request
- Responsibilities: Query via service, format CSV, download UTF-8
- Protected: `abort_unless($request->user() !== null, 403)`

**Filament Report Pages:**
- Location: `/admin/reports/ledger`, `/admin/reports/trial-balance`, etc.
- Triggers: Filament page navigation and filter/table interactions
- Responsibilities: Build query, render table with filters, provide export action

## Error Handling

**Strategy:** Validation at service boundaries; exceptions propagate to controller/page handler for user-friendly display

**Patterns:**

- `ValidationException` — raised by services when input violates business rules (e.g., account class mismatch, period closed)
- `RuntimeException` — raised by models for invariant violations (e.g., unbalanced voucher)
- Transaction rollback — entire operation fails atomically if service throws
- Filament form validation — frontend schema validation before service invocation; server-side re-validation in service
- Audit logging continues even on error (exception logged before transaction rolls back)

**Examples:**
- `EnsureOpenAccountingPeriod` throws if no open period contains accrual date
- `PostsBalancedVoucher::isBalanced()` throws if debits ≠ credits
- `ApplyWithholdingRules` validates account nature (must be withholding account per rule)
- Import service collects rejections per voucher, continues processing, returns summary with rejected reasons

## Cross-Cutting Concerns

**Logging:**
- Audit trait (`Auditable`) dispatches `ProcessAuditLog` job for every create/update event
- Captures: user ID, event type (created/updated), old/new values, IP, user agent, timestamp
- Async job prevents blocking the request

**Validation:**
- Filament schemas provide frontend validation and field constraints
- Service layer re-validates all inputs (never trust frontend)
- Database constraints (unique, foreign key, check constraints on `NOT NULL`, enum values)
- Type hints and enums prevent invalid states (e.g., `VoucherStatus::Approved` is compiler-checked in PHP 8.4)

**Authentication & Authorization:**
- Filament panel requires login; configured via `AdminPanelProvider`
- Roles: `admin`, `accountant`, `viewer`
- CSV endpoints check `$request->user() !== null`
- Policy-based authorization at resource level (to be expanded per CLAUDE.md)

**Immutability:**
- Approved vouchers: status set to `Approved` permanently; no direct edit allowed
- Correction mechanism: create adjustment voucher with `adjusts_voucher_id` reference
- Comprobantes aprobados no se editan: nueva nota de ajuste conserva trazabilidad

---

*Architecture analysis: 2026-09-16*

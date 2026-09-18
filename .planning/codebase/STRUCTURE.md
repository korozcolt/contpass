# Codebase Structure

**Analysis Date:** 2026-09-16

## Directory Layout

```
contpass/
├── app/
│   ├── Console/
│   │   └── Commands/                    # Artisan commands
│   ├── Enums/                           # Type-safe enums (VoucherStatus, PaymentMethod, etc.)
│   ├── Filament/
│   │   ├── Pages/                       # Report pages and custom pages
│   │   ├── Resources/                   # CRUD resources (auto-discovered by Filament)
│   │   ├── Support/                     # Reusable form field builders
│   │   └── Widgets/                     # Dashboard widgets
│   ├── Http/
│   │   ├── Controllers/                 # HTTP request handlers (mostly legacy)
│   │   └── Requests/                    # Form validation requests
│   ├── Jobs/                            # Queue jobs (audit logging, etc.)
│   ├── Models/                          # Eloquent models with relationships
│   ├── Providers/                       # Service providers
│   │   └── Filament/                    # Filament-specific providers
│   ├── Services/
│   │   ├── Accounting/                  # Accounting domain services
│   │   ├── Budget/                      # Budget/obligation domain services
│   │   ├── Imports/                     # Data import logic
│   │   └── Warehouse/                   # Warehouse domain services
│   └── Traits/                          # Shared model behaviors (Auditable)
├── config/                              # Laravel configuration
├── database/
│   ├── factories/                       # Model factories for testing
│   ├── migrations/                      # Database schema
│   └── seeders/                         # Database seeders
├── resources/
│   ├── css/                             # Tailwind CSS sources
│   ├── js/                              # Frontend JavaScript
│   └── views/                           # Blade templates (legacy, mostly deprecated)
├── routes/
│   └── web.php                          # Public routes (landing, CSV exports)
├── tests/
│   ├── Feature/                         # Integration/feature tests
│   └── Unit/                            # Unit tests
├── .planning/
│   └── codebase/                        # Architecture documentation (ARCHITECTURE.md, STRUCTURE.md, etc.)
└── public/                              # Static assets, Vite build output
```

## Directory Purposes

**`app/Enums/`:**
- Purpose: Type-safe enumerations for domain values
- Contains: `VoucherType`, `VoucherStatus`, `CashAccountType`, `AccountNature`, `PaymentMethod`, `ThirdPartyType`, `BudgetObligationStatus`, `PaymentOrderStatus`, `CashProgramMovementType`, `PettyCashMovementType`, `PublicEntityType`, `SignatoryArea`, `UserRole`, `EmployeeContractType`, `PayrollFundType`, `PayrollConceptType`, `WarehouseMovementType`, `WarehouseItemType`, `CompanyType`, `BudgetCertificateStatus`, `BudgetRegistrationStatus`, `BudgetModificationType`

**`app/Filament/Resources/`:**
- Purpose: CRUD interfaces for each model
- Contains: One subdirectory per resource (`IncomeRecords/`, `ExpenseRecords/`, `Payments/`, `Vouchers/`, etc.)
- Structure per resource: `[ResourceName]Resource.php` (main class), `Pages/` (Create, Edit, List, View), `Schemas/` (reusable form schemas), `Tables/` (table column definitions), `RelationManagers/` (nested resource editors)
- Key resources: `IncomeRecordResource`, `ExpenseRecordResource`, `PaymentResource`, `VoucherResource`, `ThirdPartyResource`, `ChartAccountResource`, `CashAccountResource`, `WithholdingRuleResource`, `AccountingPeriodResource`, `BudgetObligationResource`, `BudgetAppropriationResource`, `BudgetRegistrationResource`, `BudgetCertificateResource`, `PaymentOrderResource`, `WarehouseResource`, `WarehouseItemResource`, `WarehouseMovementResource`, `UserResource`, `CompanySignatoryResource`, `DependencyResource`, `EmployeeResource`, `PayrollFundResource`, `PayrollConceptResource`, `PettyCashFundResource`, `CashProgramItemResource`

**`app/Filament/Pages/`:**
- Purpose: Custom pages for reports, settings, and special operations
- Contains: Report pages (`LedgerReport`, `TrialBalanceReport`, `GeneralLedgerReport`, `BalanceSheetReport`, `IncomeStatementReport`, `JournalReport`, `AccountsReceivableReport`, `AccountsPayableReport`, `BankReconciliationReport`, `BudgetExecutionReport`), settings (`CompanySettings`), audit logs (`AuditLogs`), accountability center (`AccountabilityCenter`), warehouse reports (`WarehouseItemLedgerReport`, `WarehouseStockReport`)

**`app/Filament/Support/`:**
- Purpose: Reusable form field builders and UI utilities
- Contains: `AccountingFormFields` — builders for monetary amounts, percentages, date pickers, third parties, chart accounts, etc.

**`app/Filament/Widgets/`:**
- Purpose: Dashboard widgets
- Contains: `AccountingStats` (key metrics), `RecentVouchers` (activity stream)

**`app/Services/Accounting/`:**
- Purpose: Accounting domain services
- Contains: `PostIncomeVoucher`, `PostExpenseVoucher`, `RegisterPayment`, `ApplyWithholdingRules`, `CreateAdjustmentVoucher`, `PostsBalancedVoucher`, `EnsureOpenAccountingPeriod`, `ValidateColombianTaxId`, `BankReconciliation`, `FinancialStatement`, `AccountsReceivable`, `AccountsPayable`, `BuildVoucherNumber`, `CurrentCompany`

**`app/Services/Budget/`:**
- Purpose: Budget and obligation domain services
- Contains: `CreateBudgetObligation`, `ApproveBudgetObligation`, `IssueBudgetCertificate`, `IssuePaymentOrder`, `ExecutePaymentOrder`, `ApplyBudgetRegistration`

**`app/Services/Warehouse/`:**
- Purpose: Warehouse operations
- Contains: `BuildWarehouseMovementNumber`

**`app/Services/Imports/`:**
- Purpose: Data import logic
- Contains: `ArchiveMasterPreviewImporter` — JSON master data import with dry-run and validation

**`app/Http/Controllers/`:**
- Purpose: HTTP request handlers (mostly legacy/CSV export endpoints)
- Contains: `AccountingReportController` (ledger, trial balance, journal, financial statements, accounts receivable/payable, general ledger, bank reconciliation CSV exports), `AuthController`, `ThirdPartyController`, `ChartAccountController`, `CashAccountController`, `IncomeRecordController`, `ExpenseRecordController`, `PaymentController`, `WithholdingRuleController`, `DashboardController`
- Note: Most CRUD operations now handled by Filament; these controllers are deprecated except for CSV exports

**`app/Models/`:**
- Purpose: Eloquent model definitions with relationships
- Contains: `Voucher`, `AccountingEntry`, `IncomeRecord`, `ExpenseRecord`, `Payment`, `Company`, `ThirdParty`, `ChartAccount`, `CashAccount`, `WithholdingRule`, `AccountingPeriod`, `BudgetObligation`, `BudgetAppropriation`, `BudgetAvailabilityCertificate`, `BudgetRegistration`, `BudgetRevenue`, `BudgetModification`, `BudgetChartMapping`, `PaymentOrder`, `Warehouse`, `WarehouseItem`, `WarehouseMovement`, `WarehouseMovementLine`, `Employee`, `PayrollFund`, `PayrollConcept`, `PettyCashFund`, `PettyCashMovement`, `CashProgramItem`, `CompanySignatory`, `Dependency`, `User`

**`app/Traits/`:**
- Purpose: Reusable model behaviors
- Contains: `Auditable` — automatic async audit logging on create/update

**`app/Providers/`:**
- Purpose: Service registration and configuration
- Contains: `AdminPanelProvider` (Filament panel setup), other Laravel providers

**`database/migrations/`:**
- Purpose: Schema definitions
- Order of creation: Core (company, third parties, chart accounts, vouchers), then operations (income, expense, payment), then budget/warehouse/payroll as features added
- Naming: `YYYY_MM_DD_HHMMSS_operation_target_table.php`

**`database/factories/`:**
- Purpose: Model factories for seeding and testing
- Contains: One factory per model; used by seeders and tests

**`resources/views/`:**
- Purpose: Blade templates for legacy UI (mostly deprecated)
- Contains: `accounting-reports/` (CSV report templates), `payments/`, `income-records/`, `expense-records/`, `third-parties/`, `chart-accounts/`, `cash-accounts/`, `auth/`, `dashboard/`, `components/`, `layouts/`, `filament/` (Filament custom views)

**`routes/web.php`:**
- Purpose: Public routes
- Contains: Landing page (`/`), CSV export endpoints (`/accounting-reports/{ledger|trial-balance|...}`)

**`tests/Feature/`:**
- Purpose: Integration and feature tests
- Contains: Tests for each major resource (IncomeRecordTest, ExpenseRecordTest, PaymentTest, VoucherTest, WarehouseMovementTest, BudgetObligationTest, CashProgramItemTest, EmployeeTest, etc.)
- Pattern: Uses factories, seeders, and domain services; tests workflows end-to-end

**`tests/Unit/`:**
- Purpose: Unit tests for services and models
- Contains: Tests for service logic, model methods, and utility functions

## Key File Locations

**Entry Points:**
- `routes/web.php` — public route definitions (landing, CSV exports)
- `app/Providers/Filament/AdminPanelProvider.php` — Filament panel configuration (resources, pages, navigation groups)
- `resources/views/welcome.blade.php` — public landing page
- `app/Models/User.php` — authentication model

**Configuration:**
- `config/app.php` — application name, timezone, locale (set to `es` for Spanish)
- `config/database.php` — database connections (PostgreSQL target)
- `config/cache.php` — cache driver (Redis target)
- `config/queue.php` — queue driver (Redis target)
- `app/Providers/Filament/AdminPanelProvider.php` — Filament panel branding, colors, navigation

**Core Logic:**
- `app/Services/Accounting/` — income/expense/payment/adjustment services
- `app/Services/Budget/` — budget obligation and payment order services
- `app/Filament/Resources/` — all CRUD operations (forms and tables)

**Testing:**
- `tests/Feature/` — integration tests (create operations via domain services)
- `tests/Unit/` — unit tests for services
- `database/factories/` — model factories for test data
- `database/seeders/` — production seed data

## Naming Conventions

**Files:**
- Classes: `PascalCase.php` (e.g., `PostIncomeVoucher.php`)
- Database: `snake_case` (e.g., `accounting_entries`, `third_parties`)
- Routes: kebab-case (e.g., `/accounting-reports/ledger`)
- Filament paths: kebab-case (e.g., `/admin/income-records/create`)

**Directories:**
- Laravel standard: `app`, `config`, `database`, `resources`, `routes`, `tests`, `public`
- Subdirs: PascalCase if grouping by domain (e.g., `app/Services/Accounting/`), snake_case if legacy blade templates

**Classes:**
- Models: `PascalCase` with singular noun (e.g., `Voucher`, `IncomeRecord`, `ThirdParty`)
- Services: `PascalCase` with verb-object pattern (e.g., `PostIncomeVoucher`, `ApplyWithholdingRules`, `RegisterPayment`)
- Resources: `{Model}Resource` (e.g., `IncomeRecordResource`)
- Pages: `{Purpose}Page` or `{Purpose}Report` (e.g., `CreateIncomeRecord`, `LedgerReport`)
- Enums: `{Type}` (e.g., `VoucherStatus`, `PaymentMethod`)
- Traits: Descriptive name (e.g., `Auditable`)

**Database:**
- Tables: `snake_case` plural (e.g., `vouchers`, `accounting_entries`, `third_parties`)
- Columns: `snake_case` (e.g., `chart_account_id`, `accrual_date`, `is_deductible`)
- Foreign keys: `{model_singular}_id` (e.g., `company_id`, `voucher_id`)
- Timestamps: `created_at`, `updated_at`
- Soft deletes: `deleted_at` (not used; favor immutability or status field)

**Eloquent:**
- Relationships: camelCase, return singular/plural appropriately (e.g., `company()`, `entries()`, `incomeRecord()`)
- Accessors: camelCase, describe the value (e.g., `full_name`, `is_balanced`, `available_balance`)
- Casts: use strong types (dates, enums, collections)

## Where to Add New Code

**New Feature:**
- Primary code: `app/Services/{Domain}/` — new service class
- Resource UI: `app/Filament/Resources/{ModelName}/` — new resource and pages
- Tests: `tests/Feature/{FeatureTest}.php` — integration test
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- Model: `app/Models/{ModelName}.php` (if new table)
- Enum: `app/Enums/{TypeName}.php` (if new status/type domain)

**New Component/Module:**
- Implementation: `app/Filament/Resources/{ModuleName}/` subdirectory
- Service layer: `app/Services/{Domain}/{ServiceName}.php`
- Models: `app/Models/` (one per file)
- Factory: `database/factories/{ModelName}Factory.php`
- Tests: `tests/Feature/{ModuleTest}.php` or `tests/Unit/{ServiceTest}.php`

**Utilities / Shared Helpers:**
- Form builders: `app/Filament/Support/{BuilderName}.php`
- Traits: `app/Traits/{TraitName}.php`
- Services: `app/Services/{Domain}/{ServiceName}.php` (even if small)

## Special Directories

**`.planning/codebase/`:**
- Purpose: Architecture documentation
- Generated: Yes (ARCHITECTURE.md, STRUCTURE.md, CONVENTIONS.md, etc. by codebase mappers)
- Committed: Yes

**`database/migrations/`:**
- Purpose: Schema version control
- Generated: Yes (via `php artisan make:migration`)
- Committed: Yes

**`database/seeders/`:**
- Purpose: Reproducible seed data
- Generated: Yes (via `php artisan make:seeder`)
- Committed: Yes

**`database/factories/`:**
- Purpose: Test data generation
- Generated: Yes (via `php artisan make:factory`)
- Committed: Yes

**`public/build/`:**
- Purpose: Compiled CSS/JS from Vite
- Generated: Yes (via `npm run build`)
- Committed: No (in .gitignore)

**`storage/` and `bootstrap/cache/`:**
- Purpose: Runtime cache and logs
- Generated: Yes
- Committed: No

**`vendor/`:**
- Purpose: Composer packages
- Generated: Yes (via `composer install`)
- Committed: No

**`.git/`, `.gitignore`, `.env*`:**
- Purpose: Git and environment configuration
- Generated: Partial (migration/factory templates generate stubs)
- Committed: `.gitignore` yes; `.env.example` yes; actual `.env` no

---

*Structure analysis: 2026-09-16*

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- Document everything persistently in the repo by default — do not wait for an explicit request. This covers roadmap/phase planning, non-obvious architectural decisions, and bugs. Never let multi-phase plans or feature-gap analyses live only in conversation.
- Roadmap and phase planning — including the feature-gap comparison against the reference platform "Apolo" — lives in `docs/roadmap-apolo.md`. Read it at the start of roadmap work and update it as phases complete or scope changes.
- Bugs are tracked as GitHub Issues in this repo, not ad hoc notes. File one when a bug is found, even if fixed in the same session, and link the fixing commit/PR.
- This does not license throwaway docs for routine, single-purpose changes — it applies to roadmap/phase work, architectural decisions, and defects.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<!-- GSD:project-start source:PROJECT.md -->
## Project

**ContPass**

ContPass es una aplicación interna de control contable para empresas en Colombia (privadas y públicas/ESP), construida en Laravel 13 + Filament v5. Nació como MVP de contabilidad básica (causación, partida doble, retenciones, bancarización) y se extendió con módulos de Presupuesto Público, Tesorería, Almacén y Nómina (maestro), comparando su cobertura contra una plataforma pública de referencia llamada "Apolo" (cliente real: Aguas de Sucre S.A. E.S.P.).

**Core Value:** Cada movimiento relevante produce un comprobante contable auditable e inmutable por partida doble — trazabilidad e inmutabilidad sobre conveniencia. Los comprobantes aprobados no se editan directamente; toda corrección pasa por una nota de ajuste con historial.

### Constraints

- **Arquitectura**: la lógica contable vive en servicios de dominio (`app/Services/Accounting`, `app/Services/Budget`); los formularios Filament nunca crean asientos/registros directamente. Toda fase nueva debe seguir este patrón (ej. Fase A reusa `PostIncomeVoucher`).
- **Dependencias**: no se agregan ni cambian dependencias de Composer/NPM sin aprobación explícita del usuario (política del proyecto). Relevante para Fase D (librería de exportación Excel).
- **Estructura**: no se crean carpetas base nuevas sin aprobación; seguir la estructura de directorios existente (`app/Filament/Resources/{Entity}/`, `app/Services/{Domain}/`, etc.)
- **Testing**: todo cambio requiere test Pest nuevo o actualizado; `php artisan test --compact` debe pasar; `vendor/bin/pint --dirty --format agent` limpio antes de finalizar.
- **Idioma/locale**: español colombiano (`APP_LOCALE=es`, `APP_TIMEZONE=America/Bogota`) en toda la UI y datos de ejemplo.
- **Compatibilidad Filament v5**: cuidado con el bug de clase ya conocido — comparar `$get()` de un `Select` con `options(Enum::class)` contra el caso del enum directamente, no contra `->value` (ver issue #1).
- **CLAUDE.md del repo**: contiene las Laravel Boost guidelines checked-in — no debe sobreescribirse ni perderse al generar/refrescar documentación de proyecto.
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Languages
- PHP 8.4 - Backend application logic, Eloquent ORM, service layer
- JavaScript (Module) - Frontend assets bundling with Vite
- CSS - Styling via Tailwind CSS 4
- Composer 2.x - PHP package management
- npm/Node.js 22.x - JavaScript/frontend dependencies
## Runtime Environment
- Laravel 13.8 - Web framework, routing, database abstraction, authentication
- Filament 5.6 - Admin panel UI and form builder
- Livewire 4.x - Reactive component framework (bundled with Filament)
- Predis 3.4 - PHP Redis client for cache, sessions, and queues
## Frameworks & Libraries
- Laravel Framework 13.8 - HTTP request handling, routing, middleware
- Filament 5.6 - Admin panel, resource management, form components
- Livewire 4.x - Reactive UI components (included with Filament)
- Laravel Tinker 3.0 - REPL for application context exploration
- Laravel Pint 1.27 - PHP code formatter and linter
- Laravel Pail 1.2.5 - Log viewer in terminal
- Laravel Boost 2.2 - MCP server for enhanced dev tooling
- Laravel PAO 1.0.6 - Build tool
- Pest 4.7 - Test framework and runner
- Pest Laravel Plugin 4.1 - Pest integration with Laravel
- PHPUnit 12.x - Test infrastructure (used by Pest)
- Faker 1.23 - Test data generation
- Mockery 1.6 - Mocking library
- Vite 8.0 - JavaScript module bundler
- Lararel Vite Plugin 3.1 - Laravel integration for Vite
- Tailwind CSS 4.0 - Utility-first CSS framework
- @tailwindcss/vite 4.0 - Vite plugin for Tailwind
- concurrently 9.0.1 - Run multiple processes in parallel (dev command)
- Collision 8.6 - Pretty error reporting
- Composer Scripts - Post-install/update automation
## Key Dependencies
- `filament/filament` 5.6 - Admin UI and CRUD operations
- `laravel/framework` 13.8 - Core web framework
- `predis/predis` 3.4 - Redis client (required for cache, sessions, queues)
- Laravel Eloquent (included in framework) - ORM with support for PostgreSQL, MySQL, SQLite, SQL Server
- `laravel/boost` 2.2 - MCP server, enhanced CLI tools
- `pestphp/pest` 4.7 - Modern testing framework
- `laravel/pint` 1.27 - Code formatter and linter
## Configuration Files
- `.env` / `.env.example` - Application configuration via environment variables
- `config/app.php` - Application name, debug, timezone (defaults to America/Bogota)
- `config/contpass.php` - Custom app configuration (single-tenant company NIT)
- `config/database.php` - Database connections (PostgreSQL default in .env)
- `config/redis.php` - Redis connection pooling and options
- `config/cache.php` - Cache store drivers (Redis default in .env)
- `config/session.php` - Session driver (Redis default in .env)
- `config/queue.php` - Queue connections (Redis default in .env)
- `config/mail.php` - Email drivers (Log driver default in development)
- `vite.config.js` - Vite bundler configuration with Tailwind and Laravel plugins
- `package.json` - npm scripts and dev dependencies
- `composer.json` - PHP dependencies and composer scripts
- `phpunit.xml` - Test environment configuration
- `.editorconfig` - Editor formatting standards
- `.npmrc` - npm configuration
- `boost.json` - Laravel Boost configuration
## Platform Requirements
- PHP 8.4+
- Composer 2.x
- Node.js 22.x (recommended)
- PostgreSQL 12+ (or MySQL 8+, SQLite for local development)
- Redis 6+ (for cache, sessions, and queues)
- Laravel Herd (for local development server)
- Application runs on `https://contpass.test` via Laravel Herd
- Default timezone: America/Bogota
- Default locale: Spanish (es_CO)
- Default faker locale: Spanish Colombian (es_CO)
- PostgreSQL database (primary target)
- Redis for cache, sessions, and queue workers
- Compatible with Laravel Cloud deployment
- PHP CLI 8.4 for artisan commands
## Build Pipeline
- `npm run build` - Production Vite build (CSS, JS asset compilation)
- `npm run dev` - Development Vite server with HMR
- Fonts: IBM Plex Sans and Space Grotesk via Bunny (CDN-hosted)
- `composer install` - Install dependencies
- `php artisan migrate` - Run database migrations
- `php artisan seed` - Populate initial data
- `vendor/bin/pint` - Format PHP code
- `php artisan test` - Run Pest test suite
- `composer run dev` - Runs PHP server, queue listener, log watcher, and Vite HMR in parallel
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Naming Patterns
- PascalCase for class files: `ValidateColombianTaxId.php`, `PostIncomeVoucher.php`, `PaymentResource.php`
- Filament resources organized by domain: `Payments/PaymentResource.php`, `ChartAccounts/ChartAccountResource.php`
- Plural names for directories containing multiple related files: `Schemas/`, `Tables/`, `Pages/`
- camelCase for all public and private methods: `verificationDigit()`, `isBalanced()`, `ensureEditable()`
- Predicate methods use `is`, `has`, `can` prefixes: `isBancarized()`, `hasValidSupport()`, `canAccessPanel()`
- Service methods typically named `handle()` for the primary business logic: `PostIncomeVoucher::handle()`
- Blade/Filament helper methods use descriptive names: `companyId()`, `chartAccount()`, `thirdParty()`
- camelCase for local variables and method parameters: `$thirdParty`, `$chartAccountId`, `$amount`
- Protected/private properties use camelCase: `$postsBalancedVoucher`, `$company`
- Use descriptive names reflecting the domain concept: `$revenueAccount`, `$receivableAccount`, `$withholding`
- Boolean variables use is/has prefix: `$isDeductible`, `$hasValidSupport`, `$isBancarized`, `$isReconciled`
- PascalCase for enum names: `PaymentMethod`, `VoucherType`, `VoucherStatus`, `AccountNature`, `SignatoryArea`
- UPPERCASE for enum keys with TitleCase format: `BankTransfer`, `Check`, `LegalEntity`, `Debit`, `Credit`, `Treasury`
- Enum methods are descriptive: `label()`, `getColor()`, `getIcon()`, `isBancarized()`
## Code Style
- Use Laravel Pint for PHP code formatting. Run `vendor/bin/pint --dirty --format agent` after modifying PHP files.
- 4-space indentation throughout
- One blank line between class methods
- No trailing spaces
- Laravel Pint is the default formatter with no project-specific configuration file
- Follow PSR-12 standards as enforced by Pint
## Import Organization
- No path aliases configured; all imports use full namespaces
- App root is `App\` for all application code
- Database factories are in `Database\Factories\`
## Import Organization
## Error Handling
- Services throw `Illuminate\Validation\ValidationException` for business rule violations: `throw ValidationException::withMessages(['date' => 'El periodo contable está cerrado.']);`
- No try-catch blocks in services; exceptions propagate to framework handlers
- Framework automatically converts ValidationException to 422 responses and form errors in Filament
- Use `RuntimeException` with descriptive messages for state errors: `throw new RuntimeException('Los comprobantes aprobados no se modifican directamente.');`
- Database transactions wrap entire service operations: `DB::transaction(function () { ... });`
## Logging
- Models using the `Auditable` trait automatically dispatch audit logs on create/update
- Audit logs capture event type, user_id, model type/id, old/new values, IP, user agent, and timestamp
- Sensitive fields (e.g., password) are excluded from audit logs
- No manual logging in services; use framework facilities (Filament notifications, validation errors)
## Comments
- Minimal use of inline comments; only when logic is non-obvious
- Omit comments that restate code or method names
- Comment only WHY, not WHAT (code shows what)
- Use PHPDoc blocks for methods with complex parameters or return types: `@param array<int, array{chart_account_id: int, debit: float, credit: float}> $entries`
- Document array shapes explicitly: `@return array<string, mixed>`
- Use `@throws` only for documented exceptions
- One example in `Traits/Auditable.php`:
## Function Design
- Use type hints always: `public function handle(Company $company, string $date): void`
- Array parameters document shape in PHPDoc: `@param array{third_party_id: int, amount: float} $data`
- Nullable types use `?Type`: `?ThirdParty $thirdParty = null`
- No variadic parameters in domain logic
- Explicit return types for all methods: `: Voucher`, `: array`, `: void`
- Methods that fetch entities return models or collections: `: Collection`, `: Voucher`
- Helper methods return primitives: `: bool`, `: string`, `: float`
## Module Design
- Services export a single `handle()` method as the public interface
- Models export relationships and domain methods, not database queries
- Support classes (e.g., `AccountingFormFields`) export static helper methods
- No barrel export files (`index.php` re-exports)
- Import classes directly by full namespace
- One responsibility per service: `PostIncomeVoucher` posts income, `PostExpenseVoucher` posts expenses
- Constructor injection of dependencies: `public function __construct(private readonly PostsBalancedVoucher $postsBalancedVoucher)`
- Services wrap database operations in transactions for atomicity
- Models define relationships, casts, and domain methods only
- Business logic lives in services, not models
- Use PHP 8 constructor property promotion with visibility modifiers
- Fillable/hidden attributes defined in protected properties or attributes
## Filament Conventions
- Filament Resources located in `app/Filament/Resources/{Domain}/` with PascalCase names
- Resource class delegates form and table configuration to separate classes
- Form fields defined in `Schemas/{EntityName}Form.php`: `PaymentForm::configure($schema)`
- Table columns defined in `Tables/{EntityName}Table.php`: `PaymentsTable::configure($table)`
- Reusable form fields in `app/Filament/Support/AccountingFormFields.php`
- Money fields use COP currency prefix and icon: `TextInput::make('amount')->prefix('COP $')->prefixIcon(Heroicon::CurrencyDollar)`
- Percentage fields use `%` suffix: `TextInput::make('rate')->suffix('%')`
- Date pickers use display format 'Y-m-d': `DatePicker::make('date')->displayFormat('Y-m-d')`
- Select fields with searchable/preload for performance: `.searchable()->preload()`
- Third parties and accounts show as `identifier · name` format
- Boolean toggles over checkboxes: `ToggleColumn::make('is_reconciled')`
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## Pattern Overview
- Domain services enforce business rules (e.g., voucher balance validation, period state checking, withholding rule application)
- Filament forms are presentation-only; they invoke domain services to persist data
- Immutability by design: approved vouchers cannot be edited; corrections occur through adjustment notes
- Transactions wrap multi-step operations to maintain data consistency
- Enums enforce type safety for statuses, account natures, payment methods, and operational types
## Layers
- Purpose: Capture user intent and display data via Filament admin panel
- Location: `app/Filament/Resources`, `app/Filament/Pages`, `app/Filament/Widgets`
- Contains: Resource classes with schemas and tables, custom pages (reports), dashboard widgets
- Depends on: Domain services, models, enums
- Used by: End users via `/admin` panel
- Purpose: Handle HTTP requests and delegate to domain services
- Location: `app/Http/Controllers`, `app/Http/Requests`
- Contains: Controllers for legacy/report endpoints (mostly deprecated), custom form requests
- Depends on: Domain services, models
- Used by: Public API endpoints (`accounting-reports/*`), Filament form creation pages
- Purpose: Implement business logic and enforce rules
- Location: `app/Services/Accounting`, `app/Services/Budget`, `app/Services/Warehouse`, `app/Services/Imports`
- Contains: Service classes that validate, create, and modify models; import logic
- Depends on: Models, enums, database transactions
- Key services:
- Purpose: Define schema, relationships, and accessors
- Location: `app/Models`
- Contains: Eloquent models with relationships, casts, and computed properties
- Key models: `Company`, `ThirdParty`, `Voucher`, `AccountingEntry`, `IncomeRecord`, `ExpenseRecord`, `Payment`, `ChartAccount`, `CashAccount`, `WithholdingRule`, `AccountingPeriod`, `BudgetObligation`, `PaymentOrder`, `Warehouse`, `WarehouseItem`, `WarehouseMovement`, `Employee`, `PayrollFund`, `PayrollConcept`, `PettyCashFund`, `PettyCashMovement`, `CashProgramItem`
- Purpose: Handle cross-cutting concerns and system integrations
- Location: `app/Traits`, `app/Jobs`, `app/Providers`, `config/`, database migrations
- Contains: Audit logging trait, queue jobs, service providers, environment configuration
- Traits: `Auditable` — async audit log dispatch for all create/update events
- Jobs: `ProcessAuditLog` — asynchronous recording of model changes
- Providers: `FilamentPanelProvider` — Filament admin panel configuration and discovery
## Data Flow
- Vouchers: `Draft → Approved → [Adjusted]` or `[Voided]` (no direct editing of approved)
- Accounting periods: `Open → Closed` (blocks new causations in closed period)
- Budget obligations: `Draft → Committed → Obligated → Paid`
- Payment order: `Draft → Authorized → Executed`
- Cash program items: projections vs. executed actuals (read-only projection, executable actual)
## Key Abstractions
- Purpose: Encapsulate business rules and multi-step operations
- Examples: `PostIncomeVoucher`, `PostExpenseVoucher`, `ApplyWithholdingRules`, `RegisterPayment`, `BankReconciliation`, `FinancialStatement`
- Pattern: Each service is a single-action class (invokable or named `handle()` method); depends on other services for composition
- `VoucherType` (Income, Expense, Adjustment, BudgetObligation, Payment)
- `VoucherStatus` (Draft, Approved, Adjusted, Voided)
- `VoucherType`, `CashAccountType` (Caja, Banco)
- `AccountNature` (Asset, Liability, Equity, Revenue, Expense, Cost, CostOfSales)
- `PaymentMethod` (BankTransfer, Check, Card, Deposit, Cash, Other)
- `ThirdPartyType` (PersonaNatural, PersonaJuridica)
- `BudgetObligationStatus`, `PaymentOrderStatus`, `BudgetCertificateStatus`
- `CashProgramMovementType` (Income, Expense)
- `PettyCashMovementType` (Opening, Expense, Replenishment, Closure)
- Purpose: Declarative CRUD interfaces for models; delegate creation/update to domain services
- Pattern: `Resource` class with `form()`, `table()`, `getRelations()`; custom Create/Edit/List pages override default handlers
- Examples: `IncomeRecordResource`, `ExpenseRecordResource`, `PaymentResource`, `VoucherResource`, `ThirdPartyResource`
- Form composition via `app/Filament/Support/AccountingFormFields.php` (reusable field builders)
- Purpose: Query-driven views for analysis and monitoring
- Pattern: `Page` class implementing `HasTable`; `table()` method builds query with filters/columns/export
- Examples: `LedgerReport`, `TrialBalanceReport`, `GeneralLedgerReport`, `BalanceSheetReport`, `IncomeStatementReport`, `AccountsReceivableReport`, `AccountsPayableReport`
- Export: CSV download via table action; same logic as controller endpoint
- `Voucher` → `entries` (AccountingEntry), `incomeRecord`, `expenseRecord`, `payment`, `adjustsVoucher`, `company`, `thirdParty`
- `IncomeRecord` → `voucher`, `revenueAccount`, `receivableAccount`
- `ExpenseRecord` → `voucher`, `expenseAccount`, `payableAccount`, `budgetObligation`
- `Payment` → `voucher`, `cashAccount`, `paymentOrder`, `company`
- `BudgetObligation` → `budgetAvailabilityCertificate`, `expenseRecord`, `paymentOrder`, `company`
## Entry Points
- Location: `/admin`
- Triggers: Filament resource actions (create, update, delete, bulk, custom actions)
- Responsibilities: Form submission → domain service invocation → model persistence
- Location: `/`
- View: `resources/views/welcome.blade.php`
- Purpose: Structural overview of architecture, modules, services, and guarantees (no operational data exposed)
- Location: `GET /accounting-reports/{ledger|third-party-movements|trial-balance|journal|financial-statements|accounts-receivable|accounts-payable|general-ledger|bank-reconciliation}`
- Triggers: Authenticated GET request
- Responsibilities: Query via service, format CSV, download UTF-8
- Protected: `abort_unless($request->user() !== null, 403)`
- Location: `/admin/reports/ledger`, `/admin/reports/trial-balance`, etc.
- Triggers: Filament page navigation and filter/table interactions
- Responsibilities: Build query, render table with filters, provide export action
## Error Handling
- `ValidationException` — raised by services when input violates business rules (e.g., account class mismatch, period closed)
- `RuntimeException` — raised by models for invariant violations (e.g., unbalanced voucher)
- Transaction rollback — entire operation fails atomically if service throws
- Filament form validation — frontend schema validation before service invocation; server-side re-validation in service
- Audit logging continues even on error (exception logged before transaction rolls back)
- `EnsureOpenAccountingPeriod` throws if no open period contains accrual date
- `PostsBalancedVoucher::isBalanced()` throws if debits ≠ credits
- `ApplyWithholdingRules` validates account nature (must be withholding account per rule)
- Import service collects rejections per voucher, continues processing, returns summary with rejected reasons
## Cross-Cutting Concerns
- Audit trait (`Auditable`) dispatches `ProcessAuditLog` job for every create/update event
- Captures: user ID, event type (created/updated), old/new values, IP, user agent, timestamp
- Async job prevents blocking the request
- Filament schemas provide frontend validation and field constraints
- Service layer re-validates all inputs (never trust frontend)
- Database constraints (unique, foreign key, check constraints on `NOT NULL`, enum values)
- Type hints and enums prevent invalid states (e.g., `VoucherStatus::Approved` is compiler-checked in PHP 8.4)
- Filament panel requires login; configured via `AdminPanelProvider`
- Roles: `admin`, `accountant`, `viewer`
- CSV endpoints check `$request->user() !== null`
- Policy-based authorization at resource level (to be expanded per CLAUDE.md)
- Approved vouchers: status set to `Approved` permanently; no direct edit allowed
- Correction mechanism: create adjustment voucher with `adjusts_voucher_id` reference
- Comprobantes aprobados no se editan: nueva nota de ajuste conserva trazabilidad
<!-- GSD:architecture-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd:quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd:debug` for investigation and bug fixing
- `/gsd:execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd:profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->

# Coding Conventions

**Analysis Date:** 2026-09-16

## Naming Patterns

**Files:**
- PascalCase for class files: `ValidateColombianTaxId.php`, `PostIncomeVoucher.php`, `PaymentResource.php`
- Filament resources organized by domain: `Payments/PaymentResource.php`, `ChartAccounts/ChartAccountResource.php`
- Plural names for directories containing multiple related files: `Schemas/`, `Tables/`, `Pages/`

**Functions and Methods:**
- camelCase for all public and private methods: `verificationDigit()`, `isBalanced()`, `ensureEditable()`
- Predicate methods use `is`, `has`, `can` prefixes: `isBancarized()`, `hasValidSupport()`, `canAccessPanel()`
- Service methods typically named `handle()` for the primary business logic: `PostIncomeVoucher::handle()`
- Blade/Filament helper methods use descriptive names: `companyId()`, `chartAccount()`, `thirdParty()`

**Variables and Properties:**
- camelCase for local variables and method parameters: `$thirdParty`, `$chartAccountId`, `$amount`
- Protected/private properties use camelCase: `$postsBalancedVoucher`, `$company`
- Use descriptive names reflecting the domain concept: `$revenueAccount`, `$receivableAccount`, `$withholding`
- Boolean variables use is/has prefix: `$isDeductible`, `$hasValidSupport`, `$isBancarized`, `$isReconciled`

**Types and Enums:**
- PascalCase for enum names: `PaymentMethod`, `VoucherType`, `VoucherStatus`, `AccountNature`, `SignatoryArea`
- UPPERCASE for enum keys with TitleCase format: `BankTransfer`, `Check`, `LegalEntity`, `Debit`, `Credit`, `Treasury`
- Enum methods are descriptive: `label()`, `getColor()`, `getIcon()`, `isBancarized()`

## Code Style

**Formatting:**
- Use Laravel Pint for PHP code formatting. Run `vendor/bin/pint --dirty --format agent` after modifying PHP files.
- 4-space indentation throughout
- One blank line between class methods
- No trailing spaces

**Linting:**
- Laravel Pint is the default formatter with no project-specific configuration file
- Follow PSR-12 standards as enforced by Pint

## Import Organization

**Order:**
1. PHP built-ins and exceptions: `use RuntimeException;`
2. Illuminate framework classes: `use Illuminate\Database\Eloquent\Model;`, `use Illuminate\Support\Facades\DB;`
3. Application classes: `use App\Models\Voucher;`, `use App\Services\Accounting\PostIncomeVoucher;`
4. Traits and Interfaces at the top of class declaration: `use HasFactory, Notifiable;`

**Path Aliases:**
- No path aliases configured; all imports use full namespaces
- App root is `App\` for all application code
- Database factories are in `Database\Factories\`

## Import Organization

**Group Structure:**
```php
<?php

namespace App\Models;

// Framework imports
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Application imports
use App\Enums\VoucherType;
use App\Models\Company;

// Traits at top of class
class Voucher extends Model
{
    use HasFactory;
}
```

## Error Handling

**Patterns:**
- Services throw `Illuminate\Validation\ValidationException` for business rule violations: `throw ValidationException::withMessages(['date' => 'El periodo contable está cerrado.']);`
- No try-catch blocks in services; exceptions propagate to framework handlers
- Framework automatically converts ValidationException to 422 responses and form errors in Filament
- Use `RuntimeException` with descriptive messages for state errors: `throw new RuntimeException('Los comprobantes aprobados no se modifican directamente.');`
- Database transactions wrap entire service operations: `DB::transaction(function () { ... });`

**Service Example:**
```php
class EnsureOpenAccountingPeriod
{
    public function handle(Company $company, string $date): void
    {
        $closedPeriod = AccountingPeriod::query()
            ->whereBelongsTo($company)
            ->where('starts_on', '<=', $date)
            ->where('ends_on', '>=', $date)
            ->where('is_closed', true)
            ->exists();

        if ($closedPeriod) {
            throw ValidationException::withMessages([
                'date' => 'El periodo contable de la fecha seleccionada está cerrado.',
            ]);
        }
    }
}
```

## Logging

**Framework:** Asynchronous jobs for audit logging using `App\Jobs\ProcessAuditLog`

**Patterns:**
- Models using the `Auditable` trait automatically dispatch audit logs on create/update
- Audit logs capture event type, user_id, model type/id, old/new values, IP, user agent, and timestamp
- Sensitive fields (e.g., password) are excluded from audit logs
- No manual logging in services; use framework facilities (Filament notifications, validation errors)

**Auditable Example:**
```php
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            ProcessAuditLog::dispatch([
                'event' => 'created',
                'user_id' => Auth::id(),
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'new_values' => $model->getAttributes(),
                'timestamp' => now()->toIso8601String(),
            ]);
        });
    }
}
```

## Comments

**When to Comment:**
- Minimal use of inline comments; only when logic is non-obvious
- Omit comments that restate code or method names
- Comment only WHY, not WHAT (code shows what)

**JSDoc/PHPDoc:**
- Use PHPDoc blocks for methods with complex parameters or return types: `@param array<int, array{chart_account_id: int, debit: float, credit: float}> $entries`
- Document array shapes explicitly: `@return array<string, mixed>`
- Use `@throws` only for documented exceptions
- One example in `Traits/Auditable.php`:
```php
/**
 * Dispatch the audit job asynchronously.
 *
 * @param array<string, mixed>|null $old
 * @param array<string, mixed>|null $new
 */
protected static function dispatchAudit(string $event, self $model, ?array $old, ?array $new): void
```

## Function Design

**Size:** Service methods typically 30–50 lines; focus on orchestration, not implementation

**Parameters:**
- Use type hints always: `public function handle(Company $company, string $date): void`
- Array parameters document shape in PHPDoc: `@param array{third_party_id: int, amount: float} $data`
- Nullable types use `?Type`: `?ThirdParty $thirdParty = null`
- No variadic parameters in domain logic

**Return Values:**
- Explicit return types for all methods: `: Voucher`, `: array`, `: void`
- Methods that fetch entities return models or collections: `: Collection`, `: Voucher`
- Helper methods return primitives: `: bool`, `: string`, `: float`

**Service Pattern:**
```php
class PostIncomeVoucher
{
    public function __construct(private readonly PostsBalancedVoucher $postsBalancedVoucher) {}

    public function handle(Company $company, ThirdParty $thirdParty, array $data): Voucher
    {
        return DB::transaction(function () use ($company, $thirdParty, $data): Voucher {
            // Domain logic
        });
    }
}
```

## Module Design

**Exports:**
- Services export a single `handle()` method as the public interface
- Models export relationships and domain methods, not database queries
- Support classes (e.g., `AccountingFormFields`) export static helper methods

**Barrel Files:**
- No barrel export files (`index.php` re-exports)
- Import classes directly by full namespace

**Service Classes:**
- One responsibility per service: `PostIncomeVoucher` posts income, `PostExpenseVoucher` posts expenses
- Constructor injection of dependencies: `public function __construct(private readonly PostsBalancedVoucher $postsBalancedVoucher)`
- Services wrap database operations in transactions for atomicity

**Model Design:**
- Models define relationships, casts, and domain methods only
- Business logic lives in services, not models
- Use PHP 8 constructor property promotion with visibility modifiers
- Fillable/hidden attributes defined in protected properties or attributes

**Example Model:**
```php
class Voucher extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['company_id', 'type', 'status', 'number', 'date', 'description'];

    protected function casts(): array
    {
        return ['type' => VoucherType::class, 'status' => VoucherStatus::class, 'date' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isBalanced(): bool
    {
        // Domain method, not a query
    }
}
```

## Filament Conventions

**Resources:**
- Filament Resources located in `app/Filament/Resources/{Domain}/` with PascalCase names
- Resource class delegates form and table configuration to separate classes

**Forms and Tables:**
- Form fields defined in `Schemas/{EntityName}Form.php`: `PaymentForm::configure($schema)`
- Table columns defined in `Tables/{EntityName}Table.php`: `PaymentsTable::configure($table)`
- Reusable form fields in `app/Filament/Support/AccountingFormFields.php`

**Form Field Patterns:**
- Money fields use COP currency prefix and icon: `TextInput::make('amount')->prefix('COP $')->prefixIcon(Heroicon::CurrencyDollar)`
- Percentage fields use `%` suffix: `TextInput::make('rate')->suffix('%')`
- Date pickers use display format 'Y-m-d': `DatePicker::make('date')->displayFormat('Y-m-d')`
- Select fields with searchable/preload for performance: `.searchable()->preload()`
- Third parties and accounts show as `identifier · name` format
- Boolean toggles over checkboxes: `ToggleColumn::make('is_reconciled')`

---

*Convention analysis: 2026-09-16*

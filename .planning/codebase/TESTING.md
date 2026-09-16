# Testing Patterns

**Analysis Date:** 2026-09-16

## Test Framework

**Runner:**
- Pest v4 (PHP testing framework)
- Config: `tests/Pest.php` sets test expectations, trait extensions, and custom helpers
- PHPUnit backend: `phpunit.xml` defines test suites and environment variables

**Assertion Library:**
- Pest's `expect()` API with fluent assertions: `expect($result)->toBeTrue()`, `expect($model)->toBe($expected)`
- Database assertions via `Pest\Laravel\assertDatabaseHas()`: `assertDatabaseHas(Model::class, [...])`
- HTTP assertions: `.assertSuccessful()`, `.assertForbidden()`, `.assertHeader()`
- Livewire component assertions: `Livewire::test(...)->assertHasNoFormErrors()`

**Run Commands:**
```bash
php artisan test --compact                    # Run all tests with compact output
php artisan test --compact --filter=testName # Run specific test by name
php artisan test --compact Feature            # Run Feature tests only
php artisan test --compact Unit               # Run Unit tests only
```

## Test File Organization

**Location:**
- `tests/Feature/` for integration tests (most tests here)
- `tests/Unit/` for unit tests (ValidateColombianTaxId, EnumPresentation)
- Test files are NOT co-located with source code

**Naming:**
- PascalCase + `Test` suffix: `AccountingPostingTest.php`, `ColombianTaxIdTest.php`, `BankReconciliationTest.php`
- Name reflects the domain or feature being tested, not the file structure

**Structure:**
```
tests/
├── Feature/
│   ├── AccountingPostingTest.php
│   ├── BankReconciliationTest.php
│   ├── BudgetPostingTest.php
│   └── ...
├── Unit/
│   ├── ColombianTaxIdTest.php
│   └── EnumPresentationTest.php
├── Pest.php           # Pest configuration and extensions
└── TestCase.php       # Base test case with RefreshDatabase trait
```

## Test Structure

**Suite Organization:**

Tests are written as standalone closures using Pest's `it()` syntax. Each test is independent with setup defined in reusable fixture functions.

```php
<?php

use App\Models\Company;

function accountingFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000000']);
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id]);
    // ... more setup
    return compact('company', 'thirdParty', ...);
}

it('posts income vouchers with balanced entries', function () {
    $data = accountingFixture();
    
    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'amount' => 500000,
        // ...
    ]);
    
    expect($income->isBalanced())->toBeTrue();
});
```

**Patterns:**
- Each test calls a fixture function (e.g., `accountingFixture()`) to set up required models
- Tests invoke services directly via `app(ServiceClass::class)->handle(...)`
- Assertions use Pest's `expect()` chainable API
- Feature tests inherit `RefreshDatabase` trait for automatic rollback (defined in `tests/Pest.php`)

## Mocking

**Framework:** Pest Laravel's `mock()` function with Mockery expectations

**Patterns:**

Mocking is used sparingly, only for services that cross execution boundaries or require state isolation:

```php
use function Pest\Laravel\mock;

it('blocks cdp when amount exceeds balance', function () {
    $data = budgetFixture();
    
    // Mock CurrentCompany to return a specific company
    mock(CurrentCompany::class)->shouldReceive('get')->andReturn($company);
    
    // Service under test calls the mocked dependency
    expect(fn () => app(IssueBudgetCertificate::class)->handle(...))->toThrow(ValidationException::class);
});
```

**What to Mock:**
- Singleton services that require specific state: `CurrentCompany::class` (returns the authenticated user's company)
- External APIs (if integration required; most integrations are stubbed in config)
- Services whose behavior needs to be controlled across test boundaries

**What NOT to Mock:**
- Database models and queries — use real factories and the in-memory SQLite database
- Service methods — test the full integration path from services to models
- Validation exceptions — these are intentional behavior, not errors
- Livewire components — use `Livewire::test()` for full integration
- Filament forms and tables — test via resource classes and Livewire components

## Fixtures and Factories

**Test Data:**

Fixture functions are defined at the top of each test file. They set up the complete context needed for a test suite.

```php
function bankReconciliationFixture(): array
{
    $company = Company::factory()->create(['tax_id' => '900000003']);
    $thirdParty = ThirdParty::factory()->create([
        'company_id' => $company->id,
        'tax_id' => '900373913',
        'verification_digit' => 4,
    ]);
    $bank = ChartAccount::factory()->create([
        'company_id' => $company->id,
        'code' => '111005',
        'name' => 'Bancos',
        'nature' => AccountNature::Debit,
    ]);
    $cashAccount = CashAccount::factory()->create([
        'company_id' => $company->id,
        'chart_account_id' => $bank->id,
        'type' => CashAccountType::Bank,
    ]);
    
    return compact('company', 'thirdParty', 'bank', 'cashAccount');
}
```

**Factories:**
- Located in `database/factories/` with names like `ChartAccountFactory`, `ThirdPartyFactory`
- Extend Laravel's `Factory` base class with `definition()` method
- Define states for variations: `ChartAccountFactory::credit()` returns credit account accounts
- Use `fake()` helpers for realistic test data: `fake()->company()`, `fake()->unique()->numerify()`
- Factories are used within fixtures, not directly in tests

**Factory Example:**
```php
class ChartAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'nature' => AccountNature::Debit,
            'is_active' => true,
        ];
    }

    public function credit(): static
    {
        return $this->state(fn () => ['nature' => AccountNature::Credit]);
    }
}
```

**Location:** Fixtures live in test files, factories live in `database/factories/`

## Coverage

**Requirements:** No enforced coverage minimum

**View Coverage:**
```bash
php artisan test --coverage                  # Display coverage summary
php artisan test --coverage-html             # Generate HTML coverage report
```

## Test Types

**Unit Tests:**
- Scope: Isolated business logic without database or framework dependencies
- Approach: Test pure functions and simple classes (e.g., `ValidateColombianTaxId`)
- Examples: `tests/Unit/ColombianTaxIdTest.php` — validates DIAN verification digit calculation
- Pattern:
```php
it('calculates Colombian DIAN verification digits', function (string $taxId, int $digit) {
    $validator = new ValidateColombianTaxId;
    
    expect($validator->verificationDigit($taxId))->toBe($digit);
})->with([
    ['900373913', 4],
    ['800197268', 4],
]);
```

**Integration Tests:**
- Scope: Full domain workflows involving services, models, and database
- Approach: Set up fixtures, invoke services, assert results and side effects
- Examples: Most tests in `tests/Feature/` — test complete accounting workflows
- Pattern:
```php
it('posts income and expense vouchers with balanced entries', function () {
    $data = accountingFixture();
    
    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'revenue_account_id' => $data['revenue']->id,
        'amount' => 500000,
    ]);
    
    expect($income->isBalanced())->toBeTrue()
        ->and($income->entries)->toHaveCount(2);
});
```

**Feature Tests with Filament Components:**
- Scope: HTTP requests, Livewire component interactions, form submissions
- Approach: Use `Livewire::test()` to render Filament components, fill forms, dispatch events
- Examples: `CompanySignatoryTest.php` — tests Filament create/edit forms
- Pattern:
```php
it('can create a company signatory', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();
    
    Livewire::test(CreateCompanySignatory::class)
        ->fillForm([
            'company_id' => $company->id,
            'area' => SignatoryArea::Treasury->value,
            'full_name' => 'María Pérez',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    
    assertDatabaseHas(CompanySignatory::class, ['full_name' => 'María Pérez']);
});
```

**E2E Tests:**
- Not used; HTTP endpoint testing is minimal
- Feature tests cover the HTTP layer through Livewire component tests

## Common Patterns

**Async Testing:**

Tests automatically await job execution in testing environment (`QUEUE_CONNECTION=sync` in phpunit.xml).

```php
it('dispatches audit logs asynchronously', function () {
    $model = Company::factory()->create(['name' => 'Test Co']);
    
    // Job is executed immediately in testing
    // Audit log is created in same transaction
    $model->update(['name' => 'Updated Co']);
    
    $this->assertDatabaseHas('audit_logs', [
        'model_type' => Company::class,
        'event' => 'updated',
    ]);
});
```

**Error Testing:**

Expect exceptions thrown by services:

```php
it('blocks posting in closed accounting periods', function () {
    $data = accountingFixture();
    AccountingPeriod::factory()->closed()->create([
        'company_id' => $data['company']->id,
        'starts_on' => '2026-07-01',
        'ends_on' => '2026-07-31',
    ]);
    
    app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [
        'accrual_date' => '2026-07-15',
        'amount' => 100000,
    ]);
})->throws(ValidationException::class);
```

**Parametrized Tests:**

Use `.with()` to test multiple input/output combinations:

```php
it('calculates Colombian DIAN verification digits', function (string $taxId, int $digit) {
    expect(new ValidateColombianTaxId)->verificationDigit($taxId)->toBe($digit);
})->with([
    ['900373913', 4],
    ['800197268', 4],
    ['901362343', 2],
]);
```

**Database Assertions:**

Verify that side effects persisted correctly:

```php
it('creates adjustment vouchers for approved vouchers', function () {
    $data = accountingFixture();
    $income = app(PostIncomeVoucher::class)->handle($data['company'], $data['thirdParty'], [...]);
    
    $adjustment = app(CreateAdjustmentVoucher::class)->handle(
        $data['company'],
        $income,
        '2026-07-03',
        'Ajuste ingreso',
        [...]
    );
    
    expect($adjustment->isBalanced())->toBeTrue()
        ->and($adjustment->adjusts_voucher_id)->toBe($income->id);
    
    assertDatabaseHas(Voucher::class, [
        'id' => $income->id,
        'status' => VoucherStatus::Adjusted->value,
    ]);
});
```

## Test Isolation

- Each test runs with a fresh database (SQLite in-memory)
- `RefreshDatabase` trait defined in `tests/Pest.php` rolls back state between tests
- No test pollution or shared state between test suites
- Feature tests use `actingAs(User::factory()->create())` for authentication context

---

*Testing analysis: 2026-09-16*

---
phase: 01-cotizaci-n-electr-nica-fase-a
plan: 2
subsystem: accounting
tags: [pest, tdd, domain-services, concurrency, idempotency]

# Dependency graph
requires:
  - phase: 01-cotizaci-n-electr-nica-fase-a plan 1
    provides: "Quotation/QuotationLine models, QuotationStatus enum, factories, composite unique (company_id, number) index"
provides:
  - "App\\Services\\Accounting\\BuildQuotationNumber — company-scoped, year-scoped consecutive numbering (COT-AAAA-NNNNN)"
  - "App\\Services\\Accounting\\ConvertQuotationToIncome — idempotent Quotation(Accepted) -> Voucher/IncomeRecord conversion reusing PostIncomeVoucher unmodified"
affects: [01-03, 01-04]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "DB::transaction() + lockForUpdate() on a re-fetched row as the idempotency guard for one-click state transitions, with the real duplicate-prevention guarantee living in a DB constraint (composite unique index), not the lock"
    - "Numbering services take the parent scope model (Company) and return a formatted string; no dependency on the entity being numbered"

key-files:
  created:
    - app/Services/Accounting/BuildQuotationNumber.php
    - tests/Feature/QuotationNumberingTest.php
    - app/Services/Accounting/ConvertQuotationToIncome.php
    - tests/Feature/QuotationConversionTest.php
  modified: []

key-decisions:
  - "lockForUpdate() on BuildQuotationNumber's COUNT(*) is documented as an optimization only, not the correctness guarantee — the composite unique index (company_id, number) from Plan 1 is the real backstop for the zero-count race"
  - "ConvertQuotationToIncome::handle() takes only a Quotation (no revenue/receivable account params) because those are already chosen on the quotation itself per D-05"

patterns-established:
  - "Pattern: idempotent one-click conversions re-fetch+lockForUpdate() the row inside the same transaction that performs the state mutation, never trusting a pre-transaction status check alone"

requirements-completed: [QUOT-02, QUOT-05, QUOT-06, QUOT-07]

# Metrics
duration: ~35min
completed: 2026-09-16
---

# Phase 01 Plan 2: Quotation numbering and conversion services Summary

**`BuildQuotationNumber` (concurrency-safe COT-AAAA-NNNNN numbering, company+year scoped) and `ConvertQuotationToIncome` (idempotent one-click Quotation→Voucher conversion reusing `PostIncomeVoucher` unmodified), both TDD-tested with 8 passing cases covering the exact bugs these services were built to avoid.**

## Performance

- **Tasks:** 2 completed
- **Files modified:** 4 created

## Accomplishments
- `BuildQuotationNumber::next()` produces `COT-{año}-{00001..}` scoped by `company_id` and current year, proven independent per company and unaffected by prior-year numbers
- Proven in the same suite that the composite unique index — not `lockForUpdate()` — is what actually rejects a duplicate `company_id`+`number` pair (`UniqueConstraintViolationException` on direct `create()`)
- `ConvertQuotationToIncome::handle()` converts an `Accepted` quotation into exactly one `Voucher`+`IncomeRecord` via `PostIncomeVoucher`, sets `status = Converted` and `voucher_id`, and rejects a second conversion attempt (or a non-`Accepted` quotation) with a Spanish `ValidationException` message, without ever creating an extra `Voucher`
- `PostIncomeVoucher.php` left byte-for-byte unmodified (`git diff --stat` empty) — the idempotency guard lives entirely in the new service, per plan design

## Task Commits

Each task was committed atomically:

1. **Task 1: BuildQuotationNumber (numeración company-scoped, concurrency-safe)**
   - RED: `8e9b1aa` (test)
   - GREEN: `a232f8f` (feat)
2. **Task 2: ConvertQuotationToIncome (conversión idempotente reusando PostIncomeVoucher)**
   - RED: `532a500` (test)
   - GREEN: `e7cf947` (feat)

**Plan metadata:** committed together with this SUMMARY (see final commit)

## Files Created/Modified
- `app/Services/Accounting/BuildQuotationNumber.php` - `next(Company): string`, company+year scoped, `DB::transaction()`+`lockForUpdate()` over a `LIKE 'COT-{year}-%'` count
- `tests/Feature/QuotationNumberingTest.php` - 5 cases: first number, increment, per-company scoping, prior-year isolation, unique-index backstop
- `app/Services/Accounting/ConvertQuotationToIncome.php` - `handle(Quotation): Voucher`, re-fetch+lock guard, delegates to `PostIncomeVoucher`
- `tests/Feature/QuotationConversionTest.php` - 3 cases: successful conversion, double-conversion rejection, non-accepted rejection

## Decisions Made
None - followed plan as specified (exact code blocks, exact error message, exact numbering format all matched the plan verbatim).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Worktree was 23 commits behind local `main` and missing `.planning/`, `vendor/`, `.env`**
- **Found during:** environment setup, before Task 1
- **Issue:** This worktree's branch (`worktree-agent-ac6d1a91aa369acad`) pointed at `origin/main` (39cc548), while the shared repo's local `main` branch was 23 commits ahead at `1157867` — including all of Plan 1's `Quotation`/`QuotationLine`/`QuotationStatus` work this plan depends on. The worktree also had no `vendor/`, no `.env`, and no `.planning/` (untracked at the old commit).
- **Fix:** Copied `.env` from the main checkout, ran `composer install` (regenerated `vendor/`), created and migrated a fresh `database/database.sqlite`, then fast-forward merged `main` into the worktree branch (`git merge --ff-only main`, clean fast-forward, 0 divergent commits) after removing a temporary untracked `.planning/` copy that collided with the incoming tracked files. Re-ran `php artisan migrate` for the two new Plan 1 migrations.
- **Files modified:** none tracked by this plan (environment setup only); branch pointer advanced via fast-forward merge, no new commit content
- **Verification:** `php artisan test --compact` baseline after setup: 159/162 passing (same 3 pre-existing unrelated Vite-manifest failures documented in Plan 1's `deferred-items.md`); `app/Models/Quotation.php` and `App\Enums\QuotationStatus` present and loadable afterward
- **Committed in:** not applicable (no file changes; branch fast-forward only)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Required just to make Plan 1's dependencies available for this plan to build against. No scope creep — no plan behavior changed.

## Issues Encountered
Same 3 pre-existing `ViteManifestNotFoundException` failures as Plan 1's baseline (public welcome page, no frontend build artifact in this environment) — unrelated to this plan's services, already logged in `.planning/phases/01-cotizaci-n-electr-nica-fase-a/deferred-items.md`. Full suite after this plan: 167/170 passing (same 3 pre-existing failures, 8 new Quotation service tests passing, no regressions).

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- `app(BuildQuotationNumber::class)->next($company)` and `app(ConvertQuotationToIncome::class)->handle($quotation)` are ready for Plan 3 (Filament `QuotationResource`) to invoke directly with zero business logic in the presentation layer, exactly as the plan's success criteria requires.
- No blockers.

---
*Phase: 01-cotizaci-n-electr-nica-fase-a*
*Completed: 2026-09-16*

## Self-Check: PASSED

All 4 created files verified present on disk. All 4 task commits (`8e9b1aa`, `a232f8f`, `532a500`, `e7cf947`) verified present in `git log`.

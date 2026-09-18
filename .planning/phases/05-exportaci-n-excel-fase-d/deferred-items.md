# Deferred Items — Fase D (Exportación Excel)

## Plan 05-01

- **3 pre-existing test failures unrelated to this plan's changes** (`ExampleTest::guests_can_view_the_public_welcome_page`, `ExampleTest::authenticated_users_can_still_view_the_public_welcome_page`, `WelcomePageTest::the_welcome_page_presents_ContPass_and_links_to_the_admin_panel`), all `Illuminate\Foundation\ViteManifestNotFoundException: Vite manifest not found at .../public/build/manifest.json`. Cause: this worktree was bootstrapped fresh for this plan (`composer install`, `.env`+`key:generate`) but `npm install && npm run build` was intentionally **not** run, because Plan 05-01 only touches `app/Services/Reports/ExcelReportExporter.php` and its Pest test (no frontend surface). Per scope-boundary rules, out-of-scope failures are logged here, not fixed. Full test suite: 237 tests, 234 passed, 3 failed (all three of the above) — confirms no regression caused by this plan's changes. Run `npm install && npm run build` in this worktree before relying on the welcome page test group.

## Plan 05-03

- **Same 3 pre-existing `ViteManifestNotFoundException` failures reproduced again** — this plan's worktree was bootstrapped fresh (`composer install`, `.env`+`key:generate`) but `npm install && npm run build` was intentionally not run, since this plan only touches `app/Http/Controllers/AccountingReportController.php`, `routes/web.php`, and a Pest test (no frontend surface). Full suite: 254 tests, 251 passed, 3 failed (all three welcome-page/Vite-manifest tests) — confirms no regression from this plan's changes; the 6 new `.xlsx` route tests (13 tests) all pass.

## Plan 05-04

- **RESOLVED** — this plan's worktree ran the full bootstrap (`composer install`, `.env`+`key:generate`, `npm install && npm run build`), since Filament page changes touch the frontend build surface. With `public/build/manifest.json` present, the 3 previously-deferred `ViteManifestNotFoundException` failures from Plans 05-01/05-03 no longer reproduce. Full suite: 260 tests, 260 passed, 0 failed. No items deferred from this plan.

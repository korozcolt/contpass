# Deferred Items — Phase 02 (ReteICA por municipio)

## Pre-existing worktree environment gap (out of scope for 02-01)

- **Found during:** Plan 02-01 full-suite verification (`php artisan test --compact`)
- **Symptom:** `ExampleTest::guests_can_view_the_public_welcome_page`, `ExampleTest::authenticated_users_can_still_view_the_public_welcome_page`, `WelcomePageTest::the_welcome_page_presents_ContPass_and_links_to_the_admin_panel` fail with `ViteManifestNotFoundException` (`public/build/manifest.json` missing).
- **Cause:** This worktree (`agent-aaf8bb8263bf54947`) never had `npm install && npm run build` run — no `node_modules/`, no `public/build/`. Unrelated to any Plan 02-01 change (backend-only: migrations, models, seeder, enum, tests).
- **Scope decision:** Not fixed — out of scope per deviation-rules scope boundary (pre-existing, unrelated files). All 3 failures are present with or without Plan 02-01's changes.
- **Resolution:** Run `npm install && npm run build` in this worktree before any frontend-touching phase/plan (Plan 02-02/02-03 UI work will need this regardless).

## Orphaned legacy WithholdingRule controller/views (found during 02-02)

- **Found during:** Plan 02-02 Task 1 (migration dropping `withholding_rules.concept`)
- **Symptom:** `app/Http/Controllers/WithholdingRuleController.php`, `app/Http/Requests/StoreWithholdingRuleRequest.php`, `resources/views/withholding-rules/form.blade.php`, and `resources/views/withholding-rules/index.blade.php` all reference the `concept` column/attribute, which this plan's migration removes from `withholding_rules`.
- **Cause:** Pre-existing dead code — not wired to any route in `routes/web.php`/`routes/console.php`, not exercised by any test (confirmed via grep). Appears to predate the Filament `WithholdingRuleResource` and was superseded by it without cleanup.
- **Scope decision:** Not fixed — out of scope per deviation-rules scope boundary (unreachable/unrouted, so it doesn't break any live behavior; only the *source code* now references a nonexistent column). `ApplyWithholdingRules::orderBy('concept')` and `PostExpenseVoucher`'s `$rule->concept` interpolation, both live/reachable code paths, *were* fixed in-scope (Rule 3) as part of Task 1.
- **Resolution:** A future cleanup plan should delete these 4 orphaned files (controller, request, both blade views) or wire/replace them if a non-Filament withholding-rules UI is still desired.

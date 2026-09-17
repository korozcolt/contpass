# Deferred Items — Phase 02 (ReteICA por municipio)

## Pre-existing worktree environment gap (out of scope for 02-01)

- **Found during:** Plan 02-01 full-suite verification (`php artisan test --compact`)
- **Symptom:** `ExampleTest::guests_can_view_the_public_welcome_page`, `ExampleTest::authenticated_users_can_still_view_the_public_welcome_page`, `WelcomePageTest::the_welcome_page_presents_ContPass_and_links_to_the_admin_panel` fail with `ViteManifestNotFoundException` (`public/build/manifest.json` missing).
- **Cause:** This worktree (`agent-aaf8bb8263bf54947`) never had `npm install && npm run build` run — no `node_modules/`, no `public/build/`. Unrelated to any Plan 02-01 change (backend-only: migrations, models, seeder, enum, tests).
- **Scope decision:** Not fixed — out of scope per deviation-rules scope boundary (pre-existing, unrelated files). All 3 failures are present with or without Plan 02-01's changes.
- **Resolution:** Run `npm install && npm run build` in this worktree before any frontend-touching phase/plan (Plan 02-02/02-03 UI work will need this regardless).

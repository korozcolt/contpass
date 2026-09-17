# Deferred Items — Phase 01 (cotizacion-electronica-fase-a)

Out-of-scope discoveries found during plan execution. Not fixed as part of the current plan's task scope.

## From 01-01 (Quotation/QuotationLine models)

- **Pre-existing test failures (3), unrelated to this plan:**
  - `Tests\Feature\ExampleTest::guests_can_view_the_public_welcome_page`
  - `Tests\Feature\ExampleTest::authenticated_users_can_still_view_the_public_welcome_page`
  - `Tests\Feature\WelcomePageTest::the_welcome_page_presents_ContPass_and_links_to_the_admin_panel`
  - **Cause:** `Illuminate\Foundation\ViteManifestNotFoundException` — `public/build/manifest.json` missing (no frontend asset build run in this environment).
  - **Fix needed:** run `npm run build` (or `npm run dev` / `composer run dev`) to generate the Vite manifest. Not caused by any change in this plan; present on baseline `main` before Task 1/2 edits.

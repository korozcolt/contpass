---
phase: 02-reteica-por-municipio-fase-c
verified: 2026-09-17T05:25:19Z
status: passed
score: 5/5 must-haves verified
---

# Phase 2: ReteICA por Municipio (Fase C) Verification Report

**Phase Goal:** Usuario puede configurar y aplicar retención ICA parametrizada por municipio, sin que se acumulen retenciones de más de un municipio en una misma transacción.
**Verified:** 2026-09-17T05:25:19Z
**Status:** passed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
| --- | --- | --- | --- |
| 1 | Existe un catálogo completo DANE/DIVIPOLA (33 departamentos, ~1122 municipios) con integridad referencial FK | ✓ VERIFIED | `database/seeders/data/divipola.json` verified via `php -r` = 33 departments / 1122 municipalities; `Department`/`Municipality` models with `hasMany`/`belongsTo`; `DivipolaCatalogTest` (3/3 passing) asserts `Municipality::whereDoesntHave('department')->count() === 0` |
| 2 | Usuario puede crear una regla `WithholdingRule` tipo ICA con vigencia/tarifa/base propia, ligada a un municipio, reusando `scopeEffectiveOn` | ✓ VERIFIED | `WithholdingRule::municipality()` belongsTo, `type` cast to `WithholdingType`, `scopeEffectiveOn` untouched; `WithholdingRuleForm` shows conditional `municipality_id` Select only when `type === Ica` (bug-safe enum-case comparison, not `->value`) |
| 3 | El sistema rechaza (ValidationException) dos reglas ICA activas del mismo municipio con vigencia solapada | ✓ VERIFIED | `EnsureNoOverlappingIcaRule::handle()` throws `ValidationException::withMessages(['starts_on' => ...])`; wired into both `CreateWithholdingRule::handleRecordCreation()` and `EditWithholdingRule::handleRecordUpdate()`; `WithholdingRuleIcaTest` (5/5 passing) covers reject/allow/ignore-self/Filament-level cases |
| 4 | Reglas ICA de distintos municipios con vigencia solapada SÍ coexisten (bloqueo D-08 es por-municipio) | ✓ VERIFIED | `EnsureNoOverlappingIcaRule` filters `where('municipality_id', $municipalityId)` — scoped per municipio; `WithholdingRuleIcaTest` Test 3 explicitly asserts no exception for different municipios |
| 5 | El municipio de la causación de un gasto toma por defecto el domicilio de `Company`, editable manualmente | ✓ VERIFIED | `ExpenseRecordForm::defaultMunicipalityId()` resolves via `Company.dane_department_code`/`dane_municipality_code` → `Municipality`/`Department` code lookup; field is `->default(...)` + `->required(false)` (never blocks save); `WithholdingIcaMunicipalityTest` "defaults the expense record municipality field..." test passing |
| 6 | Causar un gasto en municipio A aplica solo la regla ICA de A, nunca la de B (y viceversa) | ✓ VERIFIED | `ApplyWithholdingRules::handle()` additive `where()`: `type != Ica OR (type == Ica AND municipality_id = X)`; `WithholdingIcaMunicipalityTest` Tests 1-3 (municipio A / B / unmatched C) all passing; end-to-end test through real `PostExpenseVoucher` entry point passing |
| 7 | ReteFuente/ReteIVA siguen aplicándose sin verse afectados por la dimensión municipio | ✓ VERIFIED | Filter's `type != Ica` branch is unconditional (not gated by `$municipalityId`); `WithholdingIcaMunicipalityTest` "never filters rete fuente rules by municipality" test passing; unmodified `AccountingPostingTest` (8/8) still passes — regression proof |

**Score:** 7/7 derived observable truths verified (mapped to 5/5 must-have truths across the 3 plans)

### Required Artifacts

| Artifact | Expected | Status | Details |
| --- | --- | --- | --- |
| `database/seeders/data/divipola.json` | Fixture DIVIPOLA con departamentos/municipios | ✓ VERIFIED | Valid JSON, 33 departments / 1122 municipalities, top-level keys `departments`/`municipalities` |
| `app/Models/Department.php` | `hasMany(Municipality::class)` | ✓ VERIFIED | Present, matches exactly |
| `app/Models/Municipality.php` | `belongsTo(Department::class)` | ✓ VERIFIED | Present, matches exactly |
| `database/seeders/DivipolaCatalogSeeder.php` | Seeder idempotente upsert | ✓ VERIFIED | `DB::table('departments')->upsert(...)` + `DB::table('municipalities')->upsert(...)`, wired via `DatabaseSeeder::run()` |
| `app/Enums/WithholdingType.php` | Enum ReteFuente/ReteIVA/Ica | ✓ VERIFIED | 3 cases with `HasColor`/`HasIcon`/`HasLabel`, matches UI-SPEC labels exactly |
| `app/Services/Accounting/EnsureNoOverlappingIcaRule.php` | Validación D-08 en capa de dominio | ✓ VERIFIED | `handle()` exported, `ValidationException::withMessages()`, per-municipio scoped, `ignoreId` param for edit-self exclusion |
| `app/Filament/Resources/WithholdingRules/Schemas/WithholdingRuleForm.php` | Select type + Select municipality_id condicional | ✓ VERIFIED | `->visible(fn (Get $get) => $get('type') === WithholdingType::Ica)` — enum case comparison, no `->value` bug |
| `app/Models/WithholdingRule.php` | type cast, municipality() belongsTo, description reemplaza concept | ✓ VERIFIED | `casts()` has `'type' => WithholdingType::class`; `municipality(): BelongsTo` present; `concept` fully absent from fillable/casts |
| `app/Services/Accounting/ApplyWithholdingRules.php` | Filtro aditivo: type != Ica pasa igual, type == Ica exige municipality_id exacto | ✓ VERIFIED | `handle()` exported with 4th optional `?int $municipalityId` param; additive `where()` closure confirmed |
| `app/Filament/Resources/ExpenseRecords/Schemas/ExpenseRecordForm.php` | Select municipality_id precargado desde Company, editable, no requerido | ✓ VERIFIED | `AccountingFormFields::municipality('municipality_id')->default(fn () => self::defaultMunicipalityId())->required(false)` |
| `app/Models/ExpenseRecord.php` | municipality_id fillable + belongsTo | ✓ VERIFIED | `municipality_id` in `$fillable`, `municipality(): BelongsTo` present |

### Key Link Verification

| From | To | Via | Status | Details |
| --- | --- | --- | --- | --- |
| `DivipolaCatalogSeeder` | `database/seeders/data/divipola.json` | `json_decode` | ✓ WIRED | `json_decode(file_get_contents(__DIR__.'/data/divipola.json'), true, 512, JSON_THROW_ON_ERROR)` |
| `Municipality` | `Department` | `belongsTo` | ✓ WIRED | Confirmed in model + exercised by `DivipolaCatalogTest` |
| `CreateWithholdingRule` | `EnsureNoOverlappingIcaRule` | `handleRecordCreation()` calls service before create, only when type=Ica | ✓ WIRED | Confirmed literal code; `WithholdingRuleIcaTest` Test 5 exercises this end-to-end |
| `WithholdingRuleForm` | `WithholdingType` enum | `Select::make('type')->options(WithholdingType::class)` | ✓ WIRED | Confirmed literal code |
| `ExpenseRecordForm` | `PostExpenseVoucher` | `CreateExpenseRecord::handleRecordCreation()` passes full `$data` (incl. `municipality_id`) | ✓ WIRED | `CreateExpenseRecord` uses Filament's default pass-through (no override needed — `municipality_id` is already part of form state); confirmed via `PostExpenseVoucher`'s `$data['municipality_id']` extraction and end-to-end test passing |
| `PostExpenseVoucher` | `ApplyWithholdingRules` | `handle()` extracts `$data['municipality_id']` and threads as 4th argument | ✓ WIRED | `$this->applyWithholdingRules->handle($company, $amount, $data['accrual_date'], $municipalityId)` confirmed literal |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| --- | --- | --- | --- |
| DIVIPOLA catalog seeds with referential integrity | `php artisan test --compact --filter=DivipolaCatalogTest` | 3/3 passed, 15 assertions | ✓ PASS |
| ICA overlap validation (reject/allow/ignore-self/Filament-level) | `php artisan test --compact --filter=WithholdingRuleIcaTest` | 5/5 passed, 6 assertions | ✓ PASS |
| Per-municipio ICA filter + ReteFuente immunity + Company-default + end-to-end | `php artisan test --compact --filter=WithholdingIcaMunicipalityTest` | 7/7 passed, 16 assertions | ✓ PASS |
| RETICA-05 regression: pre-existing expense-posting/withholding test unmodified | `php artisan test --compact --filter=AccountingPostingTest` | 8/8 passed, 25 assertions | ✓ PASS |
| Full suite regression gate | `php artisan test --compact` | 191/191 passed, 656 assertions | ✓ PASS |
| Fresh migration validity (full schema incl. this phase's 4 new/altered migrations) | `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan migrate:fresh --no-interaction --force` | Exit 0, all migrations DONE | ✓ PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| RETICA-01 | 02-01 | Catálogo DANE de municipios construido desde cero | ✓ SATISFIED | `Department`/`Municipality` schema + `divipola.json` fixture (33/1122) + `DivipolaCatalogSeeder`, tested |
| RETICA-02 | 02-02 | Regla ICA con vigencia/tarifa/base ligada a municipio, reusando versionado existente | ✓ SATISFIED | `WithholdingRule.type/municipality_id`, `scopeEffectiveOn` reused verbatim, form conditional field |
| RETICA-03 | 02-03 | Municipio de la operación toma por defecto domicilio de Company, editable | ✓ SATISFIED | `ExpenseRecordForm::defaultMunicipalityId()`, `->required(false)` |
| RETICA-04 | 02-02 + 02-03 | Solo se aplica la regla del municipio de la operación, nunca se acumulan de más de un municipio | ✓ SATISFIED | `EnsureNoOverlappingIcaRule` (config-time, 02-02) + `ApplyWithholdingRules` filter (causation-time, 02-03), both tested |
| RETICA-05 | 02-03 | ReteFuente/ReteIVA no afectados por la dimensión municipio | ✓ SATISFIED | Additive `where()` filter structurally exempts `type != Ica`; unmodified `AccountingPostingTest` passes as regression proof |

No orphaned requirements — all 5 RETICA-0X IDs declared in REQUIREMENTS.md are claimed across the 3 plans' frontmatter and independently verified above.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| --- | --- | --- | --- | --- |
| `app/Http/Controllers/WithholdingRuleController.php`, `app/Http/Requests/StoreWithholdingRuleRequest.php`, `resources/views/withholding-rules/*.blade.php` | — | Reference dropped `concept` column | ℹ️ Info | Pre-existing orphaned code (unrouted, confirmed via `grep -rn "WithholdingRuleController" routes/` = no matches, untested), logged in `deferred-items.md`. Does not affect any live behavior or the phase goal — no blocker. |
| `vendor/bin/pint --test` | `database/factories/BudgetRevenueFactory.php`, `tests/Feature/BudgetRevenueTest.php` | Formatting issues | ℹ️ Info | Pre-existing, unrelated to this phase (last touched in commit `0fb7fd4`, budget module — not in this phase's `files_modified`). All files actually touched by Phase 02 are pint-clean. |

No blocker or warning-level anti-patterns found in any file modified by this phase.

### Human Verification Required

None. All must-haves, key links, and requirements are verifiable programmatically via passing automated tests (191/191 full suite) and direct code inspection, and this phase's changes are backend/Filament-form logic without novel visual or real-time behavior requiring manual UX judgment beyond what the existing Filament v5 conventions already cover.

### Gaps Summary

No gaps. All 5 requirement IDs (RETICA-01 through RETICA-05) are satisfied with matching code, all must-have artifacts exist and are substantively wired (not stubs), the overlap-blocking (config-time) and municipality-filter (causation-time) halves of RETICA-04 both work independently and together (proven by a real end-to-end test through `PostExpenseVoucher`), and RETICA-05's non-regression is proven both structurally (additive filter exempts non-ICA types unconditionally) and empirically (unmodified `AccountingPostingTest` and full 191-test suite pass). Two informational items are logged (pre-existing orphaned legacy withholding-rule controller/views referencing the dropped `concept` column, and unrelated pint issues in the budget module) — both are out of this phase's scope and do not block goal achievement.

---

_Verified: 2026-09-17T05:25:19Z_
_Verifier: Claude (gsd-verifier)_

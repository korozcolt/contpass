# Phase 2: ReteICA por municipio (Fase C) - Research

**Researched:** 2026-09-17
**Domain:** Colombian ICA withholding tax parametrization by municipality (DIVIPOLA catalog + Laravel/Filament domain modeling)
**Confidence:** HIGH (codebase findings), MEDIUM (DIVIPOLA source format, verified via live API sample), MEDIUM (ICA rate conventions, verified via multiple market sources)

## Summary

This phase extends the existing `WithholdingRule` model (currently a flat, unfiltered rule list) with a municipality dimension so ICA retention can be parametrized per Colombian municipality without ever stacking retentions from more than one municipality on the same transaction. The codebase has **zero existing municipality/DANE catalog infrastructure** beyond two free-text nullable string columns on `companies` (`dane_department_code`, `dane_municipality_code`) — the catalog must be built from scratch, exactly as CONTEXT.md D-01/D-02/D-03 already anticipated. No new Composer dependency is required: `league/csv` (9.28.0) and `openspout/openspout` (4.32.0, reads XLSX) are already vendored transitively (via `filament/actions`), but the safest and simplest path is to convert the official DANE/DIVIPOLA dataset into a plain JSON fixture committed to the repo once, and seed it with pure `json_decode` — no library usage needed at all in application code.

The critical architectural finding is that `ApplyWithholdingRules::handle()` **today applies every active rule that matches company + date + minimum amount, with zero filtering by `concept`** — the `concept` string is purely a display label (used only in the accounting entry description and in `orderBy('concept')`), never used for business-rule filtering. This means the ICA municipality fix is additive and low-risk for ReteFuente/ReteIVA: the service only needs a new conditional branch that filters ICA-type rules by `municipality_id`, while every other rule type keeps flowing through unfiltered exactly as today (satisfying RETICA-05 by construction, not by special-casing).

The enum replacement mandated by D-04 (`concept` free text → `WithholdingType` enum) is a deliberate simplification: since `concept` was never used for filtering, collapsing it to `{ReteFuente, ReteIVA, Ica}` loses no functional behavior — only the human-readable sub-label (e.g. "Servicios" vs "Honorarios") disappears unless the planner decides to add a supplementary free-text label field. This is explicitly flagged as an open question below since CONTEXT.md leaves exact enum cases to Claude's discretion.

**Primary recommendation:** Model `departments`/`municipalities` as two normalized tables seeded from a repo-committed JSON fixture derived from the official DANE/DIVIPOLA dataset (`https://www.datos.gov.co/resource/gdxc-w37w.json`, ~1122 rows, 33 department-level divisions including Bogotá D.C.). Add `type` (`WithholdingType` enum) + nullable `municipality_id` FK to `withholding_rules`, migrate existing rows to `WithholdingType::ReteFuente`, and add a single new filter branch in `ApplyWithholdingRules` for ICA rules — do not touch the existing unfiltered path for other types.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| RETICA-01 | Catálogo de municipios colombianos (DANE) construido desde cero | DANE/DIVIPOLA source identified and verified (datos.gov.co Socrata dataset + DANE Geoportal XLSX), normalized `departments`/`municipalities` schema documented below, no dependency needed for ingestion |
| RETICA-02 | Regla ICA con vigencia/tarifa/base propia ligada a municipio, reusando versionado de `WithholdingRule` | `WithholdingRule` schema, `scopeEffectiveOn()`, and Filament resource extension points documented; migration strategy for adding `municipality_id`+`type` documented |
| RETICA-03 | Municipio de la operación por defecto = domicilio de `Company`, editable manualmente | `Company::dane_department_code`/`dane_municipality_code` (free-text today) documented as default source; `ExpenseRecordForm`/`PostExpenseVoucher` data-threading gap documented |
| RETICA-04 | Nunca se acumulan retenciones ICA de más de un municipio en la misma transacción | `ApplyWithholdingRules::handle()` current unfiltered behavior documented; exact filter branch to add specified; D-08 overlap validation approach documented |
| RETICA-05 | ReteFuente/ReteIVA sin cambios de comportamiento | Proven that `concept` was never used for filtering (only display) — confirms the ICA filter can be purely additive without touching the existing query path |

</phase_requirements>

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Catálogo de municipios (RETICA-01)**
- D-01: Modelo normalizado en dos tablas: `departments` (código DANE, nombre) y `municipalities` (FK a `departments`, código DANE de municipio, nombre) — no una tabla única desnormalizada.
- D-02: Catálogo DANE completo desde el inicio (~1122 municipios, 32 departamentos + Bogotá D.C.), no un subconjunto curado por cliente.
- D-03: La fuente de datos es el listado oficial DANE/DIVIPOLA — investigar y confirmar el formato/fuente exacta durante research, no un CSV provisto por el usuario.

**Discriminador de tipo de retención (afecta RETICA-04/05)**
- D-04: Se introduce un enum formal `WithholdingType` (ReteFuente / ReteIVA / ICA, + lo que haga falta para cubrir el `concept` actual) en reemplazo del campo `concept` como texto libre. Las reglas `WithholdingRule` existentes deben migrarse (mapear su `concept` actual al nuevo enum) como parte de esta fase — no coexistir enum+texto libre.
- D-05: `ApplyWithholdingRules` filtra las reglas tipo ICA por tipo **y** municipio exacto de la operación (además de company+fecha+monto mínimo, como ya filtra hoy). ReteFuente/ReteIVA (sin municipio) siguen aplicándose exactamente igual que hoy — ningún cambio de comportamiento para ellas.

**Municipio de la operación (RETICA-03)**
- D-06 (heredado, confirmado 2026-09-16): El municipio usado para seleccionar la regla ICA aplicable toma por defecto el domicilio registrado de la `Company` (`dane_department_code`/`dane_municipality_code`), con opción de edición manual.
- D-07: La edición manual ocurre **por transacción**, en el formulario de causación de gasto (`ExpenseRecord`) — un campo de municipio precargado con el domicilio de `Company`, editable caso a caso. No es una configuración fija a nivel `Company`.

**Conflictos entre reglas ICA (RETICA-04)**
- D-08: Al crear/editar una regla `WithholdingRule` de tipo ICA, se valida que no exista otra regla ICA activa para el **mismo municipio** con vigencia solapada — error de validación en el formulario, no se permite guardar el conflicto.

### Claude's Discretion
- Nombre exacto de las tablas/columnas del catálogo (`departments`/`municipalities` vs nombres alternativos), siempre que respete el modelo normalizado de D-01.
- Casos exactos del enum `WithholdingType` más allá de ReteFuente/ReteIVA/ICA (si el `concept` actual tiene variantes que no encajen limpio).
- Mecanismo técnico de la validación de solapamiento en D-08 (regla de validación Filament vs check en el service).
- Si el campo de municipio en `ExpenseRecord` es un select con búsqueda (dado el catálogo de ~1122 registros) — se espera `searchable()->preload()` como convención existente del proyecto, pero el detalle exacto de UX queda a discreción salvo lo que fije `02-UI-SPEC.md` si aplica (no existe ese archivo para esta fase).

### Deferred Ideas (OUT OF SCOPE)
- ReteICA por actividad económica (CIIU) del `ThirdParty` — RETICA-06, v2.
- Reportes/analítica de retención ICA por municipio — RETICA-07, v2.
- Declaración/presentación de ICA ante la Secretaría de Hacienda municipal — fuera del roadmap completo, esta fase solo calcula y registra la retención.
</user_constraints>

## Project Constraints (from CLAUDE.md)

- **Domain services own business logic**: Filament forms never create/update accounting records directly. New logic (overlap validation, ICA municipality filter) must live in `app/Services/Accounting/`, not in model boot hooks or Filament page classes.
- **No new Composer/npm dependencies without explicit user approval.** Confirmed not needed for this phase (see Standard Stack below).
- **No new base directories without approval.** Recommend `database/seeders/data/` as a subdirectory of the existing `database/seeders/` base — flag for confirmation during planning, not a new top-level folder.
- **Every change needs a Pest test**; `php artisan test --compact` must pass; `vendor/bin/pint --dirty --format agent` must be clean before finishing.
- **Spanish Colombian locale/UI** throughout (`es_CO`), `America/Bogota` timezone.
- **Filament v5 known bug**: comparing `$get()` against `Enum::Case->value` inside `visible()`/`required()` closures always returns `false` when the field uses `->options(EnumClass::class)`. Must compare against the enum case directly (`$get('type') === WithholdingType::Ica`). Directly relevant here for the municipality field's conditional visibility on `WithholdingRuleForm`.
- Services throw `ValidationException::withMessages([...])` for business-rule violations (no try/catch); this is the pattern to use for D-08's overlap check.
- Enums: PascalCase class name, TitleCase case names (`ReteFuente`, `ReteIVA`, `Ica`) — not UPPERCASE_SNAKE (confirmed against `VoucherType`, `PaymentMethod` in codebase, contradicts the generic UPPERCASE guidance in `.planning/codebase/CONVENTIONS.md`, which is stale/imprecise on this point — trust the actual enum files, not the generated doc).

## Standard Stack

### Core
No new packages required. Everything needed already exists in the Laravel/Filament stack already installed.

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP `json_decode` (built-in) | 8.4 | Parse the committed DIVIPOLA JSON fixture in the seeder | Zero-dependency, deterministic, matches `ArchiveMasterPreviewImporter`'s existing JSON-import pattern in this codebase |
| Laravel migrations + `DB::table()->upsert()` | Laravel 13.8 | Idempotent bulk-insert of ~1122 municipality rows | Standard Laravel bulk-write API; avoids 1122 individual Eloquent model instantiations |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Committed JSON fixture (offline, one-time conversion) | Runtime fetch from `datos.gov.co`/DANE Geoportal at seed time | Runtime fetch requires network access during `php artisan migrate --seed` / CI / tests, is non-deterministic if DANE updates the dataset, and needs an HTTP client + XLSX or Socrata JSON parsing at runtime. Committed JSON fixture is simpler, deterministic, and matches project's existing "vendored, offline" pattern (`ArchiveMasterPreviewImporter` reads a local JSON path, not a URL). |
| `json_decode` on a plain JSON fixture | `openspout/openspout` (reads DANE's native `.xlsx` directly) or `league/csv` (reads a CSV export) | Both are already vendored transitively (via `filament/actions`) so *no new dependency approval is needed* if the planner prefers reading the raw DANE XLSX/CSV directly instead of pre-converting to JSON. Using them directly in `App\` code without a `composer.json` entry is fragile (could disappear if Filament drops the transitive dependency in a future release) — safer to do the DANE→JSON conversion once (during plan execution, as a one-off script) and never touch openspout/league-csv from application code at all. |
| Normalized `departments`+`municipalities` tables (locked, D-01) | Single denormalized `municipalities` table with `department_code`/`department_name` columns inline | Rejected by user decision (D-01). Two tables also makes the `Company`/`WithholdingRule`/`ExpenseRecord` FKs cleaner and matches DIVIPOLA's own hierarchical structure (`cod_dpto` + `cod_mpio`). |

**Installation:** None — no `composer install`/`npm install` step needed for this phase.

**Version verification:**
```bash
composer show league/csv        # 9.28.0, installed transitively — do not add to composer.json
composer show openspout/openspout  # v4.32.0, installed transitively — do not add to composer.json
```
Confirmed present via `composer show` during research (2026-09-17). Not declared in `composer.json` — they arrived as transitive dependencies of `filament/actions`. **Do not import them in `App\` namespace code** unless the planner explicitly decides to add them to `composer.json require` with user approval; prefer the zero-dependency `json_decode` fixture approach.

## Architecture Patterns

### Recommended Project Structure
```
app/
├── Enums/
│   └── WithholdingType.php                          # NEW — ReteFuente | ReteIVA | Ica (HasLabel/HasColor/HasIcon, matches VoucherType pattern)
├── Models/
│   ├── Department.php                                # NEW
│   ├── Municipality.php                               # NEW — belongsTo Department
│   ├── WithholdingRule.php                            # MODIFIED — type cast, municipality() belongsTo
│   ├── Company.php                                    # UNCHANGED schema (discretion: optionally FK dane_* to Municipality — see Open Questions)
│   └── ExpenseRecord.php                              # MODIFIED — municipality_id fillable + belongsTo
├── Services/
│   └── Accounting/
│       ├── ApplyWithholdingRules.php                  # MODIFIED — add municipality param + ICA filter branch
│       └── EnsureNoOverlappingIcaRule.php              # NEW — D-08 overlap validation, called from Filament pages
├── Filament/
│   ├── Resources/WithholdingRules/Schemas/WithholdingRuleForm.php   # MODIFIED — type select, conditional municipality select
│   ├── Resources/WithholdingRules/Pages/CreateWithholdingRule.php   # MODIFIED — override handleRecordCreation to call EnsureNoOverlappingIcaRule
│   ├── Resources/WithholdingRules/Pages/EditWithholdingRule.php     # MODIFIED — override handleRecordUpdate similarly
│   ├── Resources/ExpenseRecords/Schemas/ExpenseRecordForm.php       # MODIFIED — municipality select, defaulted from Company
│   └── Support/AccountingFormFields.php                             # MODIFIED — new municipality()/department() select builders
database/
├── migrations/
│   ├── ..._create_departments_table.php                # NEW
│   ├── ..._create_municipalities_table.php              # NEW
│   ├── ..._add_type_and_municipality_id_to_withholding_rules_table.php  # NEW — adds columns, backfills, drops concept, updates index
│   └── ..._add_municipality_id_to_expense_records_table.php          # NEW
├── seeders/
│   ├── DatabaseSeeder.php                               # MODIFIED — calls new seeder, updates WithholdingRule seed row to use type
│   ├── DivipolaCatalogSeeder.php                        # NEW — reads JSON fixture, upserts departments+municipalities
│   └── data/
│       └── divipola.json                                # NEW — committed ~1122-row fixture (department+municipality codes/names only)
└── factories/
    ├── DepartmentFactory.php                            # NEW
    └── MunicipalityFactory.php                           # NEW
```

### Pattern 1: Additive filtering in `ApplyWithholdingRules` (preserve existing behavior)
**What:** Add a `?int $municipalityId = null` parameter and branch the query only for ICA-type rules; every other type keeps the exact current unfiltered path.
**When to use:** This is the only safe way to satisfy RETICA-04 without risking RETICA-05 (no behavior change for ReteFuente/ReteIVA).
**Example (illustrative, current file for reference):**
```php
// Source: app/Services/Accounting/ApplyWithholdingRules.php (current, read during research)
public function handle(Company $company, float $amount, string $date, ?int $municipalityId = null): Collection
{
    return WithholdingRule::query()
        ->with('chartAccount')
        ->whereBelongsTo($company)
        ->where('is_active', true)
        ->effectiveOn($date)
        ->where(function (Builder $query) use ($municipalityId): void {
            $query->where('type', '!=', WithholdingType::Ica)
                ->orWhere(function (Builder $query) use ($municipalityId): void {
                    $query->where('type', WithholdingType::Ica)
                        ->where('municipality_id', $municipalityId);
                });
        })
        ->orderBy('type')
        ->get()
        // ...rest unchanged
```
Note: today's code calls `.orderBy('concept')` — after the enum migration this must become `.orderBy('type')` (or be dropped; ordering was cosmetic, never functional).

### Pattern 2: Domain-service overlap validation (D-08), invoked from Filament page hooks
**What:** `WithholdingRule` create/edit today bypasses domain services entirely (`CreateWithholdingRule`/`EditWithholdingRule` use Filament's default `handleRecordCreation`/`handleRecordUpdate`, which call `Model::create()`/`$record->update()` directly — confirmed by reading both page classes). To add D-08's overlap check without silently violating "Filament forms are presentation-only," override these hook methods to call a new domain service first.
**When to use:** Any create/update of an ICA-type `WithholdingRule`.
**Example:**
```php
// Source: pattern derived from EnsureOpenAccountingPeriod (app/Services/Accounting/EnsureOpenAccountingPeriod.php)
class EnsureNoOverlappingIcaRule
{
    public function handle(Company $company, int $municipalityId, string $startsOn, ?string $endsOn, ?int $ignoreId = null): void
    {
        $overlaps = WithholdingRule::query()
            ->whereBelongsTo($company)
            ->where('type', WithholdingType::Ica)
            ->where('municipality_id', $municipalityId)
            ->where('is_active', true)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->where('starts_on', '<=', $endsOn ?? '9999-12-31')
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $startsOn))
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'starts_on' => 'Ya existe una regla ICA activa para este municipio con vigencia solapada.',
            ]);
        }
    }
}
```
This mirrors `EnsureOpenAccountingPeriod`'s exact shape (query + `ValidationException::withMessages`) — the established pattern in this codebase for pre-write business-rule checks.

### Pattern 3: Filament v5 conditional field visibility with enum (bug-safe)
**What:** Municipality select must only be required/visible when `type === WithholdingType::Ica`.
**When to use:** `WithholdingRuleForm`.
**Example:**
```php
// Source: docs/roadmap-apolo.md "Registro de bugs" issue #1 (already fixed elsewhere in this codebase, e.g. WarehouseMovementForm)
Select::make('type')->options(WithholdingType::class)->native(false)->live()->required(),
Select::make('municipality_id')
    ->label('Municipio')
    ->relationship('municipality', 'name') // or manual options() with searchable/preload, see Pattern 4
    ->visible(fn (Get $get): bool => $get('type') === WithholdingType::Ica)   // NOT $get('type') === 'ica' or ->value
    ->required(fn (Get $get): bool => $get('type') === WithholdingType::Ica),
```

### Pattern 4: Searchable municipality select (~1122 rows) — cascading department → municipality
**What:** A flat `searchable()->preload()` select over 1122 rows works (same scale class as `ThirdParty`/`ChartAccount` selects already in `AccountingFormFields`), but a two-step department→municipality cascade improves UX for a catalog this size.
**When to use:** `WithholdingRuleForm` (ICA rule municipality) and `ExpenseRecordForm` (transaction municipality override).
**Example:**
```php
// New builder for AccountingFormFields, following existing chartAccount()/thirdParty() pattern
public static function municipality(string $name = 'municipality_id'): Select
{
    return Select::make($name)
        ->label('Municipio')
        ->options(fn (): array => Municipality::query()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Municipality $m) => [$m->id => "{$m->name} ({$m->department->name})"])
            ->all())
        ->searchable()
        ->preload();
}
```
**Note:** `->preload()` on 1122 rows means the full list is eagerly rendered/serialized to the Livewire component on mount. This matches the project's stated convention ("se espera `searchable()->preload()` como convención existente") but is worth flagging as a performance/payload-size tradeoff — see Common Pitfalls.

### Anti-Patterns to Avoid
- **Denormalized single `municipalities` table with inline department name/code:** rejected by D-01; breaks referential integrity for department-level queries/reports.
- **Filtering ReteFuente/ReteIVA by any new column:** violates D-05/RETICA-05. The new `municipality_id` filter must apply *only* to `type === Ica`.
- **Business logic (overlap check, ICA filter) inside the `WithholdingRule` model or Filament page directly:** violates the project's domain-service architecture rule; use a dedicated service class.
- **Coexisting `concept` (free text) and `type` (enum) columns "just in case":** explicitly rejected by D-04 ("no coexistir enum+texto libre").

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Date-range overlap detection | Custom loop comparing every existing rule's dates in PHP | Single SQL query with the two-condition overlap test (`starts_on <= otherEnd AND (ends_on IS NULL OR ends_on >= otherStart)`) shown in Pattern 2 | Standard interval-overlap SQL predicate; O(1) query vs O(n) PHP loop, and matches `scopeEffectiveOn()`'s existing SQL-first style |
| DANE municipality/department names & codes | Hand-typing ~1122 rows or scraping HTML | Official DIVIPOLA JSON via `datos.gov.co` Socrata API (`https://www.datos.gov.co/resource/gdxc-w37w.json`) or DANE Geoportal XLSX (`https://geoportal.dane.gov.co/descargas/divipola/DIVIPOLA_Municipios.xlsx`) | Authoritative source, avoids transcription errors in a ~1122-row government dataset; both already verified reachable during this research |
| Cascading department→municipality select filtering | Custom JS/Livewire wire:model watchers | Filament's `Get $get` + `->options(fn (Get $get) => ...)` reactive pattern already used for `PublicEntityType` visibility in `CompanySettings.php` | Native Filament v5 reactivity, no custom JS needed, consistent with existing codebase pattern |

**Key insight:** Every piece of this phase has a directly analogous, already-proven pattern somewhere in the codebase (`EnsureOpenAccountingPeriod` for validation, `scopeEffectiveOn()` for date ranges, `chartAccountPrefixes()`/`thirdParty()` for searchable selects, `ArchiveMasterPreviewImporter` for JSON-fixture ingestion). None of this phase requires inventing a new architectural pattern — it requires careful, additive reuse.

## Common Pitfalls

### Pitfall 1: Filament v5 enum comparison bug (`$get()` vs `->value`)
**What goes wrong:** Conditional `visible()`/`required()` closures comparing `$get('type')` against `WithholdingType::Ica->value` always evaluate `false`, silently hiding the municipality field even when `type = ICA` is selected.
**Why it happens:** When a `Select` uses `->options(EnumClass::class)`, Filament v5's `$get()` returns the enum *instance*, not its scalar `->value`. Documented in this repo as issue #1, already caused 3 broken forms before the fix pattern was established.
**How to avoid:** Compare directly against the enum case: `$get('type') === WithholdingType::Ica`.
**Warning signs:** Model-level/Pest tests pass (they use enum instances directly) but the field never appears in the browser — the classic symptom already documented for this exact bug in `docs/roadmap-apolo.md`.

### Pitfall 2: `ApplyWithholdingRules` today has no functional use for `concept` — the enum replacement is a real behavior/label simplification, not a pure rename
**What goes wrong:** Assuming `WithholdingType` is a drop-in rename of `concept` with identical semantics. It is not: `concept` was free text used only for the accounting-entry description (`"Retención {$withholding['rule']->concept}"`) and cosmetic ordering — never for filtering. Collapsing it to 3 enum cases (`ReteFuente`/`ReteIVA`/`Ica`) means the current human-readable sub-labels ("Servicios", potential future "Compras", "Honorarios", etc.) are lost from the description string unless replaced by something else.
**Why it happens:** D-04 mandates replacing the free-text field entirely, but the existing single seed row (`'concept' => 'Servicios 2026'`) does not reveal the full intended granularity of "concept" as a business idea.
**How to avoid:** Decide explicitly (this is Claude's Discretion per CONTEXT.md) whether: (a) `WithholdingType` stays at exactly 3 cases and the accounting-entry description uses `$type->label()` alone (e.g. "Retención en la fuente"), losing per-rule sub-category text; or (b) keep a small supplementary nullable free-text `description`/`label` column on `WithholdingRule` for human-readable notes, independent of the type/filtering logic. Document the decision explicitly in the plan — do not let it default silently.
**Warning signs:** Existing seed/test data (`'concept' => 'Servicios 2026'`) has no automated test asserting on the description string content, so this loss could go unnoticed until a real accounting entry is inspected by a user.

### Pitfall 3: PostgreSQL vs SQLite differ on overlap-range enforcement, and the app currently has no DB-level overlap constraint at all
**What goes wrong:** Assuming a unique/exclusion index can enforce D-08's "no overlapping ICA rule per municipio" at the database layer. PostgreSQL supports `EXCLUDE USING gist` for range-overlap constraints, but SQLite (used for the entire test suite, `DB_CONNECTION=sqlite`, `:memory:`) does not support this syntax at all — a migration using it would break every test run.
**Why it happens:** The project's test suite runs on SQLite in-memory (`phpunit.xml`), which is intentionally lighter-weight than the PostgreSQL production target, but this means DB-level features not portable to SQLite cannot be used if tests must pass on both.
**How to avoid:** Implement D-08 purely in application code (`EnsureNoOverlappingIcaRule` domain service, Pattern 2), not as a database constraint. This also matches "Claude's Discretion" in CONTEXT.md, which already anticipates this choice ("regla de validación Filament vs check en el service").
**Warning signs:** A migration referencing Postgres-only DDL (`EXCLUDE USING gist`, range types) will fail immediately when `php artisan test` runs against SQLite.

### Pitfall 4: `ApplyWithholdingRules::handle()` signature change ripples to every caller
**What goes wrong:** Adding a `?int $municipalityId` parameter to `handle()` requires updating every call site, primarily `PostExpenseVoucher::handle()`, and its `$data` array shape (`@param array{...}` PHPDoc) plus the `ExpenseRecordForm`/`CreateExpenseRecord` page that assembles that array. Missing a call site means ICA rules silently never match (since `municipalityId` defaults to `null`), which fails RETICA-04 silently rather than loudly.
**Why it happens:** `PostExpenseVoucher::handle()`'s `$data` array today has no municipality field at all — it must be extended, and `ExpenseRecord` itself needs a new `municipality_id` column + fillable entry to persist what municipality was actually used (for audit/traceability — Core Value).
**How to avoid:** Grep for `ApplyWithholdingRules` and `applyWithholdingRules->handle(` before finishing the phase to confirm every call site threads the municipality value (found: only `PostExpenseVoucher.php` calls it today — no other call sites exist in the current codebase, confirmed via search during research).
**Warning signs:** A test asserting two ICA rules for different municipalities never accumulate (RETICA-04's core acceptance criterion) will only catch this if the test actually calls the full `PostExpenseVoucher` service, not `ApplyWithholdingRules` in isolation.

### Pitfall 5: ICA rates are expressed "por mil" (‰), not "por ciento" (%), by market convention
**What goes wrong:** The existing `rate` field/UI (`AccountingFormFields::percent()`, suffix `'%'`, `decimal(7,4)`, `maxValue(100)`) is percentage-oriented. Real-world ICA rates are almost universally quoted "por mil" (e.g. Bogotá services ≈ 9.66‰, Medellín general ≈ 2‰), which numerically converts to a tiny percentage (0.966%, 0.2%). The existing decimal precision (4 decimal places) and max value (100) technically accommodate this without a schema change, but if a user enters "9.66" expecting "9.66 por mil" the system will interpret it as 9.66%, a ~10x overcharge.
**Why it happens:** Colombian municipal tax convention (`por mil`) differs from the field's current percentage framing, and this is a well-established, multiply-sourced convention (verified via WebSearch across Alegra, Siesa, Siigo, Gerencie.com, Rankia).
**How to avoid:** No schema change is required (the existing `rate` column already supports the needed precision/range), but the `WithholdingRuleForm`'s ICA rate input needs a clear helper text (e.g. "Ingrese como porcentaje: 9.66‰ = 0.966%") or, at minimum, a distinct label/helper text when `type === Ica` to prevent a 10x data-entry error. Flag for planner decision — not locked by CONTEXT.md.
**Warning signs:** An ICA rule saved with `rate = 9.66` (intended as 9.66‰) would compute a withholding roughly 10x higher than intended on every matching expense.

### Pitfall 6: Bulk-seeding ~1122 rows must not slow down or couple every test run to full-catalog correctness
**What goes wrong:** If the DIVIPOLA catalog seed lives only inside a migration's `up()` method (rather than a separate `Seeder`), it runs on every `RefreshDatabase`-triggered migration (once per SQLite `:memory:` test process, per Laravel's testing docs) — acceptable performance-wise, but conflates "schema" with "reference data" and makes every feature test implicitly depend on the full catalog being present, when tests should use lightweight factories instead (per Laravel Boost guidance: "use the factories for the models" in tests, not full seeders).
**Why it happens:** No existing precedent in this codebase for a bulk-catalog seed (`DatabaseSeeder.php` only seeds a handful of hardcoded rows); this phase introduces the pattern.
**How to avoid:** Put the full DIVIPOLA fixture in a dedicated `Seeder` class (`DivipolaCatalogSeeder`, called from `DatabaseSeeder::run()`, and/or its own `php artisan db:seed --class=`), not a migration. Give `Department`/`Municipality` their own `HasFactory` factories so tests create only 1-3 rows as needed (e.g. two municipalities in two different departments, to test RETICA-04's core scenario) without ever touching the full seeder.
**Warning signs:** Test suite runtime increases noticeably after this phase, or tests fail in CI because `DatabaseSeeder` wasn't run (since `RefreshDatabase` does not call seeders by default unless `$this->seed()` is explicitly invoked).

## Code Examples

### DIVIPOLA live API sample (verified during research, 2026-09-17)
```
GET https://www.datos.gov.co/resource/gdxc-w37w.json?$limit=5

Fields returned: cod_dpto, dpto, cod_mpio, nom_mpio, tipo_municipio, longitud, latitud
Sample: {"cod_dpto":"05","dpto":"Antioquia","cod_mpio":"05001","nom_mpio":"Medellín", ...}
```
`cod_dpto` is the 2-digit department code, `cod_mpio` is the full 5-digit code (2-digit dept prefix + 3-digit municipality suffix) — this matches `Company.dane_department_code` (varchar 2) / `dane_municipality_code` (varchar 3) already in the schema, confirming the split-code convention to replicate in `municipalities.code` (3-digit, scoped under `department_id`).

### Existing `WithholdingRule` schema (current, to be extended)
```php
// Source: database/migrations/2026_07_03_175247_000005_create_withholding_rules_table.php (read during research)
Schema::create('withholding_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('chart_account_id')->constrained()->restrictOnDelete();
    $table->string('concept');
    $table->decimal('minimum_base', 15, 2)->default(0);
    $table->decimal('rate', 7, 4);
    $table->date('starts_on');
    $table->date('ends_on')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index(['company_id', 'concept', 'starts_on', 'ends_on']);
});
```
Migration plan: add `type` (string, cast to `WithholdingType`) + `municipality_id` (nullable `foreignId`, `restrictOnDelete()` per project's FK convention seen on `chart_account_id`), backfill `type = 'rete_fuente'` for all existing rows, drop `concept`, rebuild the index as `['company_id', 'type', 'municipality_id', 'starts_on', 'ends_on']`.

### `Company` DANE fields (current, free-text — source of RETICA-03 default)
```php
// Source: app/Filament/Pages/CompanySettings.php (read during research)
TextInput::make('dane_department_code')->label('Código DANE Departamento')->maxLength(2),
TextInput::make('dane_municipality_code')->label('Código DANE Municipio')->maxLength(3),
```
These are plain strings today, not FKs to any catalog — RETICA-03's default lookup must resolve `Municipality::where('code', $company->dane_municipality_code)->whereHas('department', fn ($q) => $q->where('code', $company->dane_department_code))->first()` (or similar) rather than a direct relationship, unless the planner also decides to migrate `Company` to a `municipality_id` FK (see Open Questions).

## Open Questions

1. **Should `Company.dane_department_code`/`dane_municipality_code` be migrated to a `municipality_id` FK, or left as free-text strings resolved against the new catalog at read-time?**
   - What we know: CONTEXT.md's Integration Points section explicitly flags this as "a decidir en planning." The columns are currently unconstrained free text (no validation against any catalog today).
   - What's unclear: Whether leaving them as free text risks a `Company` domicile that doesn't match any row in the new `municipalities` table (silent default-resolution failure at expense-record time).
   - Recommendation: Migrate `Company` to `municipality_id` (nullable FK) in this phase, since the catalog now exists and CompanySettings.php already exposes these fields as a form the user edits — enforcing referential integrity there costs little and directly de-risks RETICA-03's default lookup. If out of scope for effort reasons, at minimum validate the existing free-text values against the new catalog with a `Rule::exists()` check in `CompanySettings::form()`.

2. **Exact `WithholdingType` enum cases beyond ReteFuente/ReteIVA/Ica** (see Pitfall 2).
   - What we know: `concept` was never used for filtering; the only production/seed value is `'Servicios 2026'`.
   - What's unclear: Whether the business wants to preserve concept-level granularity (Servicios/Compras/Honorarios/Arrendamientos) inside ReteFuente, or fully collapse to one `ReteFuente` case.
   - Recommendation: Default to the 3-case enum (matches D-04's literal wording) plus an optional supplementary `description` nullable string column on `WithholdingRule` for the human-readable sub-label, preserving today's descriptive accounting-entry text without conflating it with the filtering discriminator.

3. **ICA rate input UX (por mil vs percent)** (see Pitfall 5).
   - What we know: No schema change is required; existing `decimal(7,4)` and `maxValue(100)` accommodate real-world ICA rates either way.
   - What's unclear: Whether the user wants the form to literally accept "por mil" input and convert, or just clearer helper text on the existing percent field.
   - Recommendation: Add helper text on `WithholdingRuleForm`'s rate field when `type === Ica`, no schema/behavior change — lowest-risk option, does not touch `ApplyWithholdingRules`' calculation (`amount * (rate / 100)`), which already correctly handles fractional percentages like `0.966`.

## Environment Availability

No external service/CLI dependency is required to execute this phase — `league/csv` and `openspout/openspout` are already vendored (see Standard Stack), and the DIVIPOLA data conversion (official source → committed JSON fixture) is a one-time, offline step during plan execution, not a runtime dependency.

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| `league/csv` (transitive) | Optional: parsing DANE CSV export during one-time fixture generation | ✓ | 9.28.0 | Use `json_decode` on Socrata's native JSON API instead (no CSV parsing needed at all) |
| `openspout/openspout` (transitive) | Optional: parsing DANE's native `.xlsx` during one-time fixture generation | ✓ | 4.32.0 | Use Socrata JSON API directly (`datos.gov.co/resource/gdxc-w37w.json`), avoiding XLSX entirely |
| Network access (one-time, during plan execution only) | Fetching the official DIVIPOLA dataset to build `database/seeders/data/divipola.json` | Assumed ✓ (verified reachable during this research session) | — | If network is unavailable during execution, the DANE Geoportal XLSX/Socrata JSON must be fetched ahead of time and staged locally before starting task execution |

**Missing dependencies with no fallback:** None.

**Missing dependencies with fallback:** None — both parsing libraries have a viable zero-dependency (`json_decode`) fallback, which is the recommended default anyway.

## Sources

### Primary (HIGH confidence)
- Direct codebase reads (2026-09-17): `app/Models/WithholdingRule.php`, `app/Services/Accounting/ApplyWithholdingRules.php`, `app/Models/Company.php`, `database/migrations/2026_07_03_175247_000005_create_withholding_rules_table.php`, `database/migrations/2026_07_30_120050_add_dane_codes_to_companies_table.php`, `app/Filament/Resources/WithholdingRules/**`, `app/Filament/Resources/ExpenseRecords/**`, `app/Models/ExpenseRecord.php`, `app/Services/Accounting/PostExpenseVoucher.php`, `app/Filament/Support/AccountingFormFields.php`, `app/Filament/Pages/CompanySettings.php`, `app/Enums/VoucherType.php`, `app/Enums/PaymentMethod.php`, `database/seeders/DatabaseSeeder.php`, `database/factories/WithholdingRuleFactory.php`, `phpunit.xml`, `tests/Pest.php`, `composer.json`
- `composer show league/csv` / `composer show openspout/openspout` — confirmed transitively installed versions
- Live API check (2026-09-17): `https://www.datos.gov.co/resource/gdxc-w37w.json?$limit=5` — confirmed dataset field names and sample rows

### Secondary (MEDIUM confidence)
- [DIVIPOLA — Códigos municipios (Datos Abiertos Colombia)](https://www.datos.gov.co/Mapas-Nacionales/DIVIPOLA-C-digos-municipios/gdxc-w37w) — 1122 rows, 7 columns, DANE-owned, last updated 2026-05-18
- [DANE Geoportal — Municipios XLSX](https://geoportal.dane.gov.co/descargas/divipola/DIVIPOLA_Municipios.xlsx) and [Departamentos XLSX](https://geoportal.dane.gov.co/descargas/divipola/DIVIPOLA_Departamentos.xlsx) — official direct download, alternative to Socrata API
- [DANE — Codificación de la División Político Administrativa de Colombia (DIVIPOLA)](https://www.dane.gov.co/index.php/sistema-estadistico-nacional-sen/normas-y-estandares/nomenclaturas-y-clasificaciones/nomenclaturas/codificacion-de-la-division-politica-administrativa-de-colombia-divipola) — official DANE landing page for the nomenclature standard
- ICA "por mil" rate convention, cross-verified across: [Alegra — Retención de ICA 2026](https://blog.alegra.com/colombia/certificado-retencion-de-ica/), [Siesa — Guía ReteICA](https://www.siesa.com/blog/guia-reteica-colombia/), [Siigo — Qué es el ReteICA](https://www.siigo.com/blog/obligaciones-fiscales/que-es-reteica-y-cuando-se-aplica/), [Gerencie.com — Retención en la fuente por ICA](https://www.gerencie.com/retencion-en-la-fuente-en-el-ica.html), [Rankia — Impuesto ICA 2026](https://www.rankia.co/blog/dian/3510937-impuesto-ica-porcentaje-formulario-tarifas)

### Tertiary (LOW confidence)
- None used as authoritative — all findings above were cross-verified against either direct codebase reads or an official government source.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — verified directly via `composer show` and file reads, no external claims relied upon
- Architecture: HIGH — every recommended pattern is a direct, verified extension of existing code in this repo (not invented)
- DIVIPOLA source/format: MEDIUM — verified live via the Socrata API and cross-referenced against the DANE Geoportal, but exact row count (~1122) and field completeness should be re-confirmed at execution time since it is a live government dataset that can change
- ICA rate convention (por mil): MEDIUM — verified across 5 independent Colombian accounting/tax sources (Alegra, Siesa, Siigo, Gerencie, Rankia), all agreeing on the "por mil" framing, but exact per-municipality rates are Claude's Discretion / out of scope for this phase (rules are user-configured, not seeded with real municipal rates)
- Pitfalls: HIGH — pitfalls 1, 3, 4 are directly evidenced by reading the actual codebase/config files (not inferred); pitfalls 2, 5, 6 are reasoned from verified facts (unused `concept` filtering, SQLite test config, market rate convention)

**Research date:** 2026-09-17
**Valid until:** 30 days for architecture/codebase findings (stable, internal); DIVIPOLA source URLs should be re-verified if execution happens more than ~60 days after this research, since it is a live government dataset subject to periodic administrative updates (new municipios, boundary changes)

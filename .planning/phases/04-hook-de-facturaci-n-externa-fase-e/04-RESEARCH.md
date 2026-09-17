# Phase 4: Hook de facturación externa (Fase E) - Research

**Researched:** 2026-09-17
**Domain:** Laravel 13 / Filament v5 — modelo relacionado 1:1 + acción de tabla con modal-formulario
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **D-01:** La referencia se captura vía una acción dedicada ("Registrar factura externa") en `IncomeRecordsTable`, no un campo embebido en `IncomeRecordForm`. La acción abre un modal con el formulario de la referencia — nunca toca la página de edición del `IncomeRecord` ni sus campos.
- **D-02:** La acción cambia de label/comportamiento según exista o no una referencia: "Registrar factura externa" si no existe, "Ver/editar factura externa" si ya existe (dado D-05, hasOne).
- **D-03:** La referencia es editable libremente después de capturada — no requiere nota de ajuste ni historial de corrección. Es metadata de referencia, nunca toca el `Voucher`/`IncomeRecord`, así que corregir un dato mal capturado no compromete el Core Value de inmutabilidad contable.
- **D-04:** Campos: `invoice_number` (único obligatorio), `cufe` (opcional, sin validación de formato/longitud), `provider` (opcional, texto libre — sin catálogo de proveedores), `document_url` (opcional). Solo `invoice_number` es requerido en el formulario.
- **D-05 (CUFE):** El CUFE se acepta como texto libre cuando se ingresa, sin validar formato hex/longitud (96 caracteres del estándar DIAN). No hay verificación real contra DIAN en esta fase.
- **D-06:** Un `IncomeRecord` tiene como máximo UNA referencia de factura externa (`hasOne`), no varias. Coincide con el roadmap y con el precedente `BudgetObligation::hasOne(PaymentOrder::class)`.

### Claude's Discretion

- Nombre exacto del modelo/tabla (`ExternalInvoiceReference` es la sugerencia del roadmap, no una decisión cerrada con el usuario).
- Estructura exacta del modal/formulario de la acción Filament (layout de campos, iconografía).
- Si la migración usa nombres de columna en inglés (`invoice_number`, `cufe`, `provider`, `document_url`) o español — seguir la convención ya usada en el resto del esquema (columnas en inglés, labels en español en la UI).

### Deferred Ideas (OUT OF SCOPE)

- **INVHOOK-04 (integración activa API/webhook):** ya reconocido como v2 — no discutido en profundidad.
- **Catálogo fijo de proveedores de facturación electrónica:** descartado — texto libre en `provider`.
- **Validación de formato del CUFE:** descartada por fricción sin garantía real.
- **Múltiples referencias por IncomeRecord (hasMany):** descartada — el roadmap y success criteria hablan de una referencia singular.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| INVHOOK-01 | Usuario puede registrar manualmente una referencia de factura electrónica externa (número, CUFE, proveedor, URL del documento) asociada a un `IncomeRecord`, después de creado | `Action::make(...)->form([...])->action(...)` en `IncomeRecordsTable` (precedente: `QuotationsTable::reject`, ver Pattern 1); `IncomeRecord::hasOne(ExternalInvoiceReference::class)` (precedente: `Voucher::hasOne(IncomeRecord::class)`, `BudgetObligation::hasOne(PaymentOrder::class)`) |
| INVHOOK-02 | La referencia de factura externa se guarda en un registro relacionado propio, sin modificar el `IncomeRecord` inmutable | Nueva tabla `external_invoice_references` con FK `income_record_id`; la acción de tabla nunca escribe en `income_records` (ver Architecture Patterns) |
| INVHOOK-03 | Ninguna parte del sistema intenta llamar a una API externa de facturación usando estos campos | Modelo/servicio sin llamadas HTTP salientes; verificable por ausencia de `Http::` / clientes HTTP en el código nuevo (ver Common Pitfalls) |
</phase_requirements>

## Summary

Esta fase es un CRUD acotado sobre un modelo relacionado 1:1, expuesto como una acción de tabla (no un Resource, no un RelationManager) sobre `IncomeRecordsTable`. El proyecto ya tiene TODOS los precedentes de código necesarios — no hace falta ningún patrón nuevo ni librería nueva:

1. **Acción de tabla con formulario modal que crea/edita un registro relacionado:** `QuotationsTable::reject` (`app/Filament/Resources/Quotations/Tables/QuotationsTable.php:64-85`) usa exactamente `Action::make(...)->form([...])->action(function (Model $record, array $data) {...})`. Esto contradice la nota de CONTEXT.md ("no tienen precedente exacto") — sí lo hay, aunque no pre-rellena el form con datos existentes de un modelo relacionado (ver Pitfall 1 sobre cómo resolverlo con `fillForm`/`mountUsing`).
2. **Relación 1:1 (`hasOne`) con FK propia en la tabla hija:** `Voucher::hasOne(IncomeRecord::class)` y `BudgetObligation::hasOne(PaymentOrder::class)` son el patrón Eloquent. **Importante:** la migración de `payment_orders` NO tiene `unique()` en `budget_obligation_id` — es un hueco de integridad en el precedente que esta fase NO debe copiar (D-06 exige "a lo sumo una"). El patrón correcto con `unique()` real ya existe en `quotations.voucher_id` (`->nullable()->unique()->constrained('vouchers')->nullOnDelete()`).
3. **RelationManagers SÍ existen en el proyecto** (`BudgetRegistrations/RelationManagers/BudgetObligationsRelationManager.php`, `PettyCashFunds/RelationManagers/MovementsRelationManager.php`, `WarehouseMovements/RelationManagers/LinesRelationManager.php`, etc.) — otra corrección a CONTEXT.md ("no hay RelationManagers en el proyecto" es falso). No obstante, D-01 es una decisión ya cerrada con el usuario (acción de tabla, no RelationManager) — no se reabre esta alternativa, se documenta solo para que el planner no asuma erróneamente que se está inventando un patrón sin precedente.
4. **Testing de acciones de tabla con formulario:** `tests/Feature/QuotationLifecycleTest.php` (líneas 57-67) usa `TestAction::make('reject')->table($quotation)` + `callAction(..., ['rejection_reason' => ''])->assertHasFormErrors([...])` — patrón directo a reusar para testear la acción "Registrar factura externa".

**Primary recommendation:** Modelo `ExternalInvoiceReference` (tabla `external_invoice_references`, FK `income_record_id` con `->unique()`), sin trait `Auditable` (consistente con `IncomeRecord`/`ExpenseRecord`, que tampoco lo usan), acción única `Action::make('external_invoice')` en `IncomeRecordsTable` con label/icono condicional (D-02) y `->fillForm(fn (IncomeRecord $record) => $record->externalInvoiceReference?->toArray() ?? [])` para el caso de edición, persistiendo con `updateOrCreate` dentro del `->action()` closure (sin service de dominio nuevo — es un upsert de metadata, no lógica contable).

## Standard Stack

No aplica una tabla de librerías nuevas — esta fase no requiere ninguna dependencia Composer/NPM nueva. Todo se construye con el stack ya instalado:

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| filament/filament | 5.6.8 (verificado, `composer show`) | `Filament\Actions\Action`, formularios modales de tabla | Ya en uso extensivo en el proyecto |
| laravel/framework | 13.18.1 (verificado, `php artisan --version`) | Eloquent `hasOne`/`belongsTo`, migraciones | Ya en uso |
| pestphp/pest | 4.7 | Test de la acción y del modelo | Convención del proyecto |

**Instalación:** Ninguna — no se agregan dependencias (política del proyecto, `CLAUDE.md`: "no se agregan ni cambian dependencias de Composer/NPM sin aprobación explícita").

## Architecture Patterns

### Recommended Project Structure

```
app/Models/ExternalInvoiceReference.php          # nuevo — modelo simple, sin Auditable
app/Filament/Resources/IncomeRecords/
├── Tables/IncomeRecordsTable.php                # editar — agregar Action::make('external_invoice')
database/migrations/
└── {timestamp}_create_external_invoice_references_table.php   # nuevo
database/factories/
└── ExternalInvoiceReferenceFactory.php          # nuevo
tests/Feature/
└── ExternalInvoiceReferenceTest.php             # nuevo (o similar nombre)
```

No se toca `IncomeRecordForm.php`, `CreateIncomeRecord.php`, `EditIncomeRecord.php` (D-01), ni `IncomeRecordResource.php::getRelations()` (no es un RelationManager).

### Pattern 1: Acción de tabla con formulario modal que persiste un modelo relacionado 1:1

**What:** Una `Action` de Filament añadida a `->recordActions()` de una tabla, con `->form([...])` (array de componentes, NO `->schema()` — la sintaxis de acciones sigue siendo `form()` en Filament v5 para modales de acción, confirmado en el código instalado) y un closure `->action(function (Model $record, array $data) {...})` que hace el `updateOrCreate`.

**When to use:** Cuando el registro relacionado es 1:1, opcional, y no amerita su propio Resource/RelationManager (exactamente el caso de esta fase, decisión D-01 ya cerrada).

**Example (precedente real, `app/Filament/Resources/Quotations/Tables/QuotationsTable.php:64-85`):**
```php
Action::make('reject')
    ->label('Rechazar')
    ->icon(Heroicon::XCircle)
    ->color('danger')
    ->visible(fn (Quotation $record): bool => $record->effectiveStatus() === QuotationStatus::Sent)
    ->modalHeading(fn (Quotation $record): string => "Rechazar {$record->number}")
    ->modalDescription('Vas a marcar esta cotización como Rechazada. Indica el motivo:')
    ->form([
        Textarea::make('rejection_reason')
            ->label('Motivo de rechazo')
            ->required()
            ->validationMessages(['required' => 'Debes indicar un motivo de rechazo.'])
            ->maxLength(255),
    ])
    ->action(function (Quotation $record, array $data): void {
        $record->forceFill([
            'status' => QuotationStatus::Rejected,
            'rejection_reason' => $data['rejection_reason'],
        ])->save();

        Notification::make()->success()->title('Cotización rechazada')->send();
    }),
```

**Adaptación para esta fase:**
```php
Action::make('external_invoice')
    ->label(fn (IncomeRecord $record): string => $record->externalInvoiceReference ? 'Ver/editar factura externa' : 'Registrar factura externa')
    ->icon(Heroicon::DocumentText)
    ->color('gray')
    ->fillForm(fn (IncomeRecord $record): array => $record->externalInvoiceReference?->toArray() ?? [])
    ->form([
        TextInput::make('invoice_number')->label('Número de factura')->required(),
        TextInput::make('cufe')->label('CUFE'),
        TextInput::make('provider')->label('Proveedor'),
        TextInput::make('document_url')->label('URL del documento')->url(),
    ])
    ->action(function (IncomeRecord $record, array $data): void {
        $record->externalInvoiceReference()->updateOrCreate([], $data);

        Notification::make()->success()->title('Factura externa registrada')->send();
    }),
```

`updateOrCreate([], $data)` sobre una relación `hasOne` ya scoped (`$record->externalInvoiceReference()`) es seguro: Eloquent añade automáticamente `income_record_id` como condición/valor. La validación de unicidad de `invoice_number` (D-04) debe excluir el propio registro en edición — usar `Rule::unique('external_invoice_references', 'invoice_number')->ignore($record->externalInvoiceReference?->id)` en el `TextInput::make('invoice_number')->unique(...)`, ver Pitfall 2.

### Pattern 2: Relación 1:1 con FK única en la tabla hija

**Precedente correcto a seguir (`database/migrations/2026_09_16_210230_create_quotations_table.php:25`):**
```php
$table->foreignId('voucher_id')->nullable()->unique()->constrained('vouchers')->nullOnDelete();
```

**Aplicado a esta fase:**
```php
Schema::create('external_invoice_references', function (Blueprint $table) {
    $table->id();
    $table->foreignId('income_record_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('invoice_number')->unique();
    $table->string('cufe')->nullable();
    $table->string('provider')->nullable();
    $table->string('document_url')->nullable();
    $table->timestamps();
});
```

`cascadeOnDelete()` (no `nullOnDelete()`, la FK no es nullable aquí porque siempre hay exactamente un `income_record_id` por fila) — coherente con `income_records.voucher_id` (`->constrained()->cascadeOnDelete()`, sin `nullable()`). Si el `IncomeRecord` padre se borra, la referencia externa no tiene sentido huérfana.

**Modelo:**
```php
// app/Models/IncomeRecord.php — agregar:
public function externalInvoiceReference(): HasOne
{
    return $this->hasOne(ExternalInvoiceReference::class);
}
```

```php
// app/Models/ExternalInvoiceReference.php — nuevo
class ExternalInvoiceReference extends Model
{
    use HasFactory;

    protected $fillable = ['income_record_id', 'invoice_number', 'cufe', 'provider', 'document_url'];

    public function incomeRecord(): BelongsTo
    {
        return $this->belongsTo(IncomeRecord::class);
    }
}
```

Sin `Auditable` — consistente con `IncomeRecord` y `ExpenseRecord` (ninguno de los dos usa el trait; solo lo usan modelos con ciclo de vida de aprobación como `Voucher`, `BudgetObligation`, `Quotation`). D-03 confirma que esta referencia no necesita historial de corrección.

### Anti-Patterns to Avoid

- **No copiar el hueco de integridad de `payment_orders.budget_obligation_id`:** esa FK no tiene `unique()` a nivel de base de datos pese a que `BudgetObligation::hasOne(PaymentOrder::class)` lo sugiere en Eloquent. Sin la restricción DB, nada impide crear dos `PaymentOrder` para la misma obligación por fuera de Filament (seeders, tinker, importaciones futuras). Para `external_invoice_references`, `income_record_id` DEBE llevar `->unique()` explícito.
- **No crear un service de dominio (`app/Services/...`) para este upsert.** El CLAUDE.md del proyecto reserva `app/Services/{Domain}` para lógica de negocio contable/presupuestal con `handle()`; un `updateOrCreate` de metadata sin reglas de negocio (D-03: editable libremente, sin validación de formato) no amerita esa capa — hacerlo sería sobre-ingeniería para este caso.
- **No usar `->schema()` en la Action** — esa API es para `Filament\Schemas\Schema` (usado en `Resource::form()` y `RelationManager::form()`), mientras que las acciones de tabla usan `->form(array $components)` (confirmado: ningún archivo del proyecto usa `Action::make(...)->schema(...)`, todos usan `->form([...])`, ver `QuotationsTable::reject`).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Pre-rellenar el modal con datos existentes al editar | Lógica manual de `mount()`/query en el Livewire component | `->fillForm(fn (IncomeRecord $record) => $record->externalInvoiceReference?->toArray() ?? [])` (usa `CanBeMounted::fillForm()`, ya vendorizado en `filament/actions`) | Es el mecanismo oficial de Filament v5 para poblar un formulario de acción con datos de un registro relacionado; evita reimplementar `mountUsing` a mano |
| Validar unicidad de `invoice_number` excluyendo el registro propio en edición | Query manual `where('invoice_number', ...)->where('id', '!=', ...)` dentro del `->action()` | `TextInput::make('invoice_number')->unique(ignoreRecord: true, ...)` o `Rule::unique(...)->ignore(...)` en el schema | Filament/Laravel ya resuelven la exclusión del registro actual; hacerlo a mano en el closure duplica lógica y es fácil de dejar desincronizado con el schema |

**Key insight:** Esta fase es deliberadamente pequeña (D-03: sin nota de ajuste, D-05: sin validación de formato del CUFE) — el riesgo real no es "faltan patrones", es sobre-construir donde el propio alcance pide simplicidad.

## Common Pitfalls

### Pitfall 1: `fillForm` en una Action de tabla requiere resolver el registro relacionado, no el propio `$record`

**What goes wrong:** Si se escribe `->fillForm(fn (IncomeRecord $record) => $record->toArray())` el formulario se llena con los campos de `IncomeRecord` (no existen `invoice_number`/`cufe` ahí), o si se olvida el `?->` null-safe, el closure lanza error cuando el registro relacionado no existe aún (caso "Registrar" por primera vez).
**Why it happens:** `CanBeMounted::fillForm()` (`vendor/filament/actions/src/Concerns/CanBeMounted.php:31-38`) simplemente llama `$schema?->fill($action->evaluate($data))` — el closure recibe el `$record` de la fila (tipo `IncomeRecord`), no el modelo relacionado automáticamente.
**How to avoid:** `->fillForm(fn (IncomeRecord $record): array => $record->externalInvoiceReference?->toArray() ?? [])` — siempre con `?->` y fallback a `[]` para el caso "no existe aún".
**Warning signs:** Modal se abre vacío cuando debería mostrar datos existentes, o Livewire lanza `Call to a member function on null`.

### Pitfall 2: `unique()` en el `TextInput` sin `ignoreRecord` bloquea la edición de una referencia ya guardada

**What goes wrong:** D-04 exige `invoice_number` único. Si se usa `TextInput::make('invoice_number')->unique(table: 'external_invoice_references')` sin excluir el registro actual, el usuario no podrá re-guardar el modal de edición (D-03: "editable libremente") porque el propio valor ya existe en la tabla con su propio `id`.
**Why it happens:** La regla `unique` de Filament, por defecto, no sabe qué registro se está editando dentro de una Action de tabla (a diferencia de un formulario de `EditRecord` donde Filament infiere el `$record` del Resource automáticamente).
**How to avoid:** Firma verificada en `vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:563`: `unique(string|Closure|null $table, string|Closure|null $column, Model|Closure|null $ignorable, ?bool $ignoreRecord, ?Closure $modifyRuleUsing)`. El default de `$ignorable` (cuando `ignoreRecord: true`) es `$component->getRecord()`, que en el contexto de esta Action es el `IncomeRecord` de la fila — NO el `ExternalInvoiceReference`, así que el default es incorrecto aquí. Pasar `$ignorable` explícito: `TextInput::make('invoice_number')->unique(table: 'external_invoice_references', ignorable: fn (IncomeRecord $record): ?ExternalInvoiceReference => $record->externalInvoiceReference)`.
**Warning signs:** Test de "editar una referencia existente sin cambiar el número" falla con error de unicidad.

### Pitfall 3: Confundir `nullOnDelete()` con `cascadeOnDelete()` en la FK 1:1

**What goes wrong:** Si `income_record_id` se declara `nullable()->nullOnDelete()` (copiando el patrón de `quotations.voucher_id`, que es nullable porque una cotización puede no tener voucher aún), un borrado del `IncomeRecord` dejaría filas huérfanas en `external_invoice_references` con `income_record_id = null`, violando la relación 1:1 real de este caso (la referencia SIEMPRE pertenece a un `IncomeRecord` existente, nunca se crea antes).
**Why it happens:** Copiar el patrón de `quotations.voucher_id` sin notar que ahí la nulidad tiene un motivo de negocio distinto (voucher se asigna después de convertir la cotización) que no aplica aquí (la referencia solo se crea sobre un `IncomeRecord` ya existente, D-01).
**How to avoid:** `income_record_id` NO debe ser `nullable()`; usar `cascadeOnDelete()` como en `income_records.voucher_id`.

### Pitfall 4: Validar accidentalmente que INVHOOK-03 se cumple

**What goes wrong:** Es fácil "verificar" INVHOOK-03 solo con inspección visual y olvidarlo en un test regresivo — un futuro cambio podría agregar sin querer una llamada HTTP en el modelo o en el `->action()` closure (p. ej., al integrar con DIAN en INVHOOK-04 sin aislar el cambio).
**Why it happens:** No hay una prueba automatizada que falle si aparece una integración activa.
**How to avoid:** No es necesario mockear un HTTP client inexistente; basta con que el test feature de la acción no dependa de ningún fake de red y que el modelo `ExternalInvoiceReference` no implemente ningún método que llame a `Http::` — mantenerlo así es la propia garantía. Si se quiere una señal explícita, se puede documentar en el PHPDoc de la clase: "Sin integración activa (INVHOOK-03) — ver INVHOOK-04 para la fase futura."

## Code Examples

### Test de la acción (precedente exacto: `tests/Feature/QuotationLifecycleTest.php:57-67`)

```php
// Source: tests/Feature/QuotationLifecycleTest.php (patrón existente, adaptado)
use App\Filament\Resources\IncomeRecords\Pages\ListIncomeRecords;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('registra una referencia de factura externa sobre un IncomeRecord existente', function () {
    $this->actingAs(User::factory()->create());
    $incomeRecord = IncomeRecord::factory()->create();

    Livewire::test(ListIncomeRecords::class)
        ->callAction(
            TestAction::make('external_invoice')->table($incomeRecord),
            ['invoice_number' => 'FE-001', 'cufe' => null, 'provider' => 'Proveedor X', 'document_url' => null],
        );

    expect($incomeRecord->fresh()->externalInvoiceReference->invoice_number)->toBe('FE-001');
});

it('exige invoice_number para registrar la factura externa', function () {
    $this->actingAs(User::factory()->create());
    $incomeRecord = IncomeRecord::factory()->create();

    Livewire::test(ListIncomeRecords::class)
        ->callAction(TestAction::make('external_invoice')->table($incomeRecord), ['invoice_number' => ''])
        ->assertHasFormErrors(['invoice_number' => 'required']);
});
```

**Nota:** `ListIncomeRecords` es la página `index` (única listada en `IncomeRecordResource::getPages()` junto a `create`; no existe página `view`/`edit` separada visible en tabla — confirmar en planning si la tabla se renderiza en `ListIncomeRecords` o si hace falta una página `EditIncomeRecord` para acceder a la tabla; `IncomeRecordResource::getPages()` actual no incluye `'edit'` como ruta pero el archivo `Pages/EditIncomeRecord.php` sí existe — ver Open Questions).

## Open Questions

1. **`EditIncomeRecord.php` existe pero no está registrado en `IncomeRecordResource::getPages()`**
   - What we know: el archivo `app/Filament/Resources/IncomeRecords/Pages/EditIncomeRecord.php` existe en disco, pero `IncomeRecordResource::getPages()` (leído en este research) solo registra `'index'` y `'create'` — no `'edit'`.
   - What's unclear: si esto es código huérfano (como el caso ya documentado en `STATE.md` para `WithholdingRuleController`) o si falta registrarlo y actualmente es inalcanzable por URL.
   - Recommendation: no bloquea esta fase (D-01 usa una acción de tabla, no la página de edición), pero el planner debería registrar esto como hallazgo de código huérfano si no está ya documentado, o simplemente ignorarlo si es intencional (tabla ya editable inline vía `EditAction` en la lista). Verificar rápidamente con `php artisan route:list --name=income-records` durante planning.

2. ~~Firma exacta del parámetro `ignorable`/`ignoreRecord` en `unique()`~~ — **RESUELTO durante research**, ver Pitfall 2. Verificado directamente en `vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:563-593`. Alternativa equivalente si se prefiere sintaxis Laravel pura: `Rule::unique('external_invoice_references', 'invoice_number')->ignore($record->externalInvoiceReference?->id)` en `->rules([...])`.

## Sources

### Primary (HIGH confidence — código del proyecto, leído directamente)
- `app/Models/BudgetObligation.php`, `app/Models/Voucher.php`, `app/Models/IncomeRecord.php` — patrón `hasOne`/`belongsTo`
- `app/Filament/Resources/Quotations/Tables/QuotationsTable.php` — precedente exacto de `Action::make(...)->form([...])->action(...)`
- `app/Filament/Resources/IncomeRecords/{IncomeRecordResource,Tables/IncomeRecordsTable,Pages/CreateIncomeRecord}.php` — estructura actual a extender
- `tests/Feature/QuotationLifecycleTest.php` — patrón de test `TestAction::make(...)->table(...)` + `assertHasFormErrors`
- `database/migrations/2026_09_16_210230_create_quotations_table.php`, `2026_09_17_100000_create_bank_statement_imports_table.php`, `..._create_payment_orders_table.php`, `..._create_income_records_table.php` — convenciones de FK/unique/cascade
- `vendor/filament/actions/src/Concerns/CanBeMounted.php` — implementación real de `fillForm`/`mountUsing`
- `database/factories/{IncomeRecordFactory,BudgetObligationFactory}.php` — convención de factories
- `composer show filament/filament` (v5.6.8), `php artisan --version` (Laravel 13.18.1) — versiones verificadas en el entorno real, no asumidas de training data

### Secondary (MEDIUM confidence)
- Ninguna — todo lo crítico se verificó contra código/comandos reales del proyecto, sin necesidad de WebSearch.

### Tertiary (LOW confidence)
- Ninguna.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — sin dependencias nuevas, todo el stack ya está instalado y verificado con `composer show`/`php artisan --version`.
- Architecture: HIGH — cada patrón citado tiene un precedente real leído directamente del código del proyecto (`QuotationsTable::reject`, `Voucher::hasOne`, `quotations.voucher_id` FK).
- Pitfalls: HIGH para los 4 — todos verificados contra código fuente vendorizado o convención existente del proyecto, incluida la firma exacta de `unique()` en `CanBeValidated.php:563`.

**Research date:** 2026-09-17
**Valid until:** ~30 días (stack estable, sin dependencias externas de terceros involucradas en esta fase)

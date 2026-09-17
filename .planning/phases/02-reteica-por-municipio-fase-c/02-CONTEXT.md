# Phase 2: ReteICA por municipio (Fase C) - Context

**Gathered:** 2026-09-17
**Status:** Ready for planning

<domain>
## Phase Boundary

Permitir configurar y aplicar retención ICA parametrizada por municipio: construir desde cero un catálogo de municipios colombianos (DANE/DIVIPOLA), extender `WithholdingRule` para que una regla ICA quede ligada a un municipio específico, y corregir `ApplyWithholdingRules` para que nunca se apliquen retenciones ICA de más de un municipio en la misma transacción — sin afectar el comportamiento actual de ReteFuente/ReteIVA. El municipio de la operación por defecto es el domicilio registrado de la `Company`, editable manualmente por transacción. Sin declaración de ICA ante la Secretaría de Hacienda municipal, sin ReteICA por actividad económica (CIIU) del `ThirdParty` — eso queda fuera de esta fase (ver roadmap).

</domain>

<decisions>
## Implementation Decisions

### Catálogo de municipios (RETICA-01)
- **D-01:** Modelo normalizado en dos tablas: `departments` (código DANE, nombre) y `municipalities` (FK a `departments`, código DANE de municipio, nombre) — no una tabla única desnormalizada.
- **D-02:** Catálogo DANE completo desde el inicio (~1122 municipios, 32 departamentos + Bogotá D.C.), no un subconjunto curado por cliente. Evita bloquear a un cliente nuevo que opere en un municipio no cubierto.
- **D-03:** La fuente de datos es el listado oficial DANE/DIVIPOLA — investigar y confirmar el formato/fuente exacta durante research, no un CSV provisto por el usuario.

### Discriminador de tipo de retención (afecta RETICA-04/05)
- **D-04:** Se introduce un enum formal `WithholdingType` (ReteFuente / ReteIVA / ICA, + lo que haga falta para cubrir el `concept` actual) en reemplazo del campo `concept` como texto libre. Las reglas `WithholdingRule` existentes deben migrarse (mapear su `concept` actual al nuevo enum) como parte de esta fase — no coexistir enum+texto libre.
- **D-05:** `ApplyWithholdingRules` filtra las reglas tipo ICA por tipo **y** municipio exacto de la operación (además de company+fecha+monto mínimo, como ya filtra hoy). ReteFuente/ReteIVA (sin municipio) siguen aplicándose exactamente igual que hoy — ningún cambio de comportamiento para ellas.

### Municipio de la operación (RETICA-03)
- **D-06 (heredado, confirmado 2026-09-16):** El municipio usado para seleccionar la regla ICA aplicable toma por defecto el domicilio registrado de la `Company` (`dane_department_code`/`dane_municipality_code`), con opción de edición manual.
- **D-07:** La edición manual ocurre **por transacción**, en el formulario de causación de gasto (`ExpenseRecord`) — un campo de municipio precargado con el domicilio de `Company`, editable caso a caso cuando el servicio/compra se originó en otro municipio. No es una configuración fija a nivel `Company`.

### Conflictos entre reglas ICA (RETICA-04)
- **D-08:** Al crear/editar una regla `WithholdingRule` de tipo ICA, se valida que no exista otra regla ICA activa para el **mismo municipio** con vigencia solapada — error de validación en el formulario, no se permite guardar el conflicto. Consistente con el Core Value de trazabilidad/auditoría del proyecto (no dejar ambigüedades silenciosas).

### Claude's Discretion
- Nombre exacto de las tablas/columnas del catálogo (`departments`/`municipalities` vs nombres alternativos), siempre que respete el modelo normalizado de D-01.
- Casos exactos del enum `WithholdingType` más allá de ReteFuente/ReteIVA/ICA (si el `concept` actual tiene variantes que no encajen limpio).
- Mecanismo técnico de la validación de solapamiento en D-08 (regla de validación Filament vs check en el service).
- Si el campo de municipio en `ExpenseRecord` es un select con búsqueda (dado el catálogo de ~1122 registros) — se espera `searchable()->preload()` como convención existente del proyecto, pero el detalle exacto de UX queda a discreción salvo lo que fije `02-UI-SPEC.md` si aplica.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Alcance y decisiones ya tomadas
- `docs/roadmap-apolo.md` §"Plan de desarrollo: mejoras comerciales para mercado privado" → Fase C — alcance original
- `.planning/PROJECT.md` — Core Value, constraints de arquitectura/testing/dependencias, Key Decisions (incluye la decisión previa de domicilio de `Company` para ICA, confirmada 2026-09-16, y el out-of-scope explícito de ReteICA por CIIU)
- `.planning/REQUIREMENTS.md` — RETICA-01 a RETICA-05 (requirements exactos de esta fase), RETICA-06/07 explícitamente diferidos a v2

### Código fuente de referencia (patrones a reusar o corregir, no clonar ciegamente)
- `app/Models/WithholdingRule.php` — modelo a extender: agregar FK de municipio (nullable, solo requerido para tipo ICA) y migrar `concept` texto libre al nuevo enum `WithholdingType`
- `database/migrations/2026_07_03_175247_000005_create_withholding_rules_table.php` — migración original, índice compuesto `(company_id, concept, starts_on, ends_on)` a revisar tras el cambio de `concept`→enum+municipio
- `app/Services/Accounting/ApplyWithholdingRules.php` — service a corregir: hoy filtra solo por `whereBelongsTo($company)`, `is_active`, `effectiveOn($date)`, sin ningún filtro de tipo/concepto. Debe agregar filtro por tipo ICA + municipio exacto sin tocar el comportamiento de ReteFuente/ReteIVA
- `app/Models/Company.php` + `database/migrations/2026_07_30_120050_add_dane_codes_to_companies_table.php` — ya tiene `dane_department_code`, `dane_municipality_code`, `city` (strings sueltos, no FK) — fuente del default de D-06
- `app/Filament/Pages/CompanySettings.php` — formulario existente de los campos DANE de `Company`, patrón de referencia
- `app/Filament/Resources/WithholdingRules/Schemas/WithholdingRuleForm.php` y `Tables/WithholdingRulesTable.php` — recurso Filament existente a extender con el campo de municipio y el enum de tipo
- `app/Services/Accounting/PostExpenseVoucher.php` — el `$data` array de causación de gasto no captura municipio hoy; debe extenderse para threadear el campo nuevo de `ExpenseRecord` hacia `ApplyWithholdingRules`
- `.planning/codebase/CONVENTIONS.md`, `.planning/codebase/STRUCTURE.md` — convenciones de Filament Resource y enums (TitleCase, UPPERCASE keys)

### Bug conocido de Filament v5 (aplica a esta fase)
- `docs/roadmap-apolo.md` §"Registro de bugs" issue #1 — comparar `$get()` contra el caso del enum directamente, nunca contra `->value`, en cualquier campo condicional nuevo (ej. mostrar el campo de municipio solo cuando el tipo de retención sea ICA)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `WithholdingRule::scopeEffectiveOn()` — scope de vigencia ya probado, reusar tal cual para el filtro de fecha de las reglas ICA.
- Campos DANE existentes en `Company` (`dane_department_code`, `dane_municipality_code`) — fuente directa del default de municipio, sin necesidad de nueva captura de datos en `Company`.
- Patrón de recurso Filament `WithholdingRuleForm`/`WithholdingRulesTable` — extender, no reescribir.
- `AccountingFormFields` — helper reusable para selects de cuentas contables, aplica también al nuevo select de municipio si sigue el mismo patrón `searchable()->preload()`.

### Established Patterns
- Enums en `app/Enums/` con TitleCase de clase y UPPERCASE de casos (`VoucherStatus`, `PaymentMethod`, etc.) — `WithholdingType` debe seguir el mismo patrón.
- Servicios de dominio en `app/Services/Accounting/` con método `handle()` — cualquier lógica nueva (ej. validación de solapamiento) vive en un service, no en el modelo/resource directamente.
- Validación de reglas de negocio vía `ValidationException::withMessages([...])`, no try/catch — aplica a la validación de solapamiento de D-08.

### Integration Points
- `ApplyWithholdingRules::handle()` conecta con `PostExpenseVoucher` — el municipio de la operación debe threadearse desde el formulario de `ExpenseRecord` hasta esta llamada.
- El nuevo catálogo `municipalities`/`departments` se referencia desde tres lugares: `Company` (opcionalmente migrar los strings DANE existentes a FK, a decidir en planning), `WithholdingRule` (FK obligatoria para tipo ICA), y `ExpenseRecord` (FK para el municipio de la operación).

</code_context>

<specifics>
## Specific Ideas

- Modelo normalizado `departments` + `municipalities`, no tabla única.
- Catálogo DANE/DIVIPOLA completo (~1122 municipios), no subconjunto por cliente.
- Enum `WithholdingType` reemplaza `concept` texto libre — migración de datos existentes incluida en el alcance de esta fase.
- Campo de municipio editable por transacción en el formulario de `ExpenseRecord`, precargado con el domicilio de `Company`.
- Validación de solapamiento de reglas ICA del mismo municipio al guardar (bloqueo duro, no advertencia).

</specifics>

<deferred>
## Deferred Ideas

- ReteICA por actividad económica (CIIU) del `ThirdParty` — ya en REQUIREMENTS.md como RETICA-06, v2. Descartado deliberadamente para v1 (ver Key Decisions en PROJECT.md).
- Reportes/analítica de retención ICA por municipio — ya en REQUIREMENTS.md como RETICA-07, v2.
- Declaración/presentación de ICA ante la Secretaría de Hacienda municipal — explícitamente fuera de alcance del roadmap; esta fase solo calcula y registra la retención.

### Reviewed Todos (not folded)
Ninguno — no había todos pendientes que coincidieran con esta fase (`todo match-phase` devolvió 0 resultados).

</deferred>

---

*Phase: 02-reteica-por-municipio-fase-c*
*Context gathered: 2026-09-17*

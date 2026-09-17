# Phase 4: Hook de facturación externa (Fase E) - Context

**Gathered:** 2026-09-17
**Status:** Ready for planning

<domain>
## Phase Boundary

Capturar manualmente una referencia de factura electrónica emitida por un proveedor tercero (número, CUFE, proveedor, URL del documento) asociada a un `IncomeRecord` ya creado, en un registro relacionado propio — sin modificar el `IncomeRecord` inmutable ni intentar ninguna integración activa (API/webhook) con el proveedor.

</domain>

<decisions>
## Implementation Decisions

### Captura y edición
- **D-01:** La referencia se captura vía una acción dedicada ("Registrar factura externa") en `IncomeRecordsTable`, no un campo embebido en `IncomeRecordForm`. La acción abre un modal con el formulario de la referencia — nunca toca la página de edición del `IncomeRecord` ni sus campos.
- **D-02:** La acción cambia de label/comportamiento según exista o no una referencia: "Registrar factura externa" si no existe, "Ver/editar factura externa" si ya existe (dado D-05, hasOne).
- **D-03:** La referencia es editable libremente después de capturada — no requiere nota de ajuste ni historial de corrección. Es metadata de referencia, nunca toca el `Voucher`/`IncomeRecord`, así que corregir un dato mal capturado no compromete el Core Value de inmutabilidad contable.

### Campos
- **D-04:** Campos: `invoice_number` (único obligatorio), `cufe` (opcional, sin validación de formato/longitud), `provider` (opcional, texto libre — sin catálogo de proveedores), `document_url` (opcional). Solo `invoice_number` es requerido en el formulario.
- **D-05 (CUFE):** El CUFE se acepta como texto libre cuando se ingresa, sin validar formato hex/longitud (96 caracteres del estándar DIAN). Coherente con INVHOOK-03 ("solo captura, sin integración activa") — no hay verificación real contra DIAN en esta fase, así que validar el formato exacto no aporta garantía, solo fricción ante variantes de formato no verificadas por proveedor.

### Cardinalidad
- **D-06:** Un `IncomeRecord` tiene como máximo UNA referencia de factura externa (`hasOne`), no varias. Coincide con el roadmap ("modelo `ExternalInvoiceReference` relacionado 1:1") y con el precedente ya existente en el código (`BudgetObligation::hasOne(PaymentOrder::class)`). Si el usuario se equivoca, la edita (D-03) en vez de crear una segunda.

### Claude's Discretion
- Nombre exacto del modelo/tabla (`ExternalInvoiceReference` es la sugerencia del roadmap, no una decisión cerrada con el usuario).
- Estructura exacta del modal/formulario de la acción Filament (layout de campos, iconografía).
- Si la migración usa nombres de columna en inglés (`invoice_number`, `cufe`, `provider`, `document_url`) o español — seguir la convención ya usada en el resto del esquema (columnas en inglés, labels en español en la UI).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Diseño original de Fase E
- `docs/roadmap-apolo.md` §"Fase E: Hook de integración con facturador electrónico de terceros" (líneas 175-181) — define el alcance base (campos nullable en `IncomeRecord` o modelo `ExternalInvoiceReference` relacionado 1:1; captura manual desde el formulario Filament de Ingresos; explícitamente fuera: integración activa por API/webhook, que se define en fase futura).

### Patrón de relación 1:1 a reusar
- `app/Models/BudgetObligation.php` — `hasOne(PaymentOrder::class)` es el precedente directo del patrón que este hook necesita para `IncomeRecord::hasOne(ExternalInvoiceReference::class)` (o el nombre de modelo que se elija).
- `app/Models/IncomeRecord.php` — modelo existente al que se relaciona; no debe modificarse su tabla/columnas (D-01, la referencia vive en un registro relacionado propio, nunca en `income_records`).

### Estructura Filament existente a reusar
- `app/Filament/Resources/IncomeRecords/IncomeRecordResource.php`, `Tables/IncomeRecordsTable.php`, `Pages/{Create,Edit,List}IncomeRecord.php` — estructura existente; la nueva acción "Registrar factura externa" (D-01) se añade a `IncomeRecordsTable.php`, sin tocar `IncomeRecordForm.php` ni las páginas Create/Edit.

### Requisitos y alcance excluido
- `.planning/REQUIREMENTS.md` §"Hook de Facturación Externa (INVHOOK) — Fase E" — INVHOOK-01 a INVHOOK-03 (esta fase) e INVHOOK-04 (integración activa por API/webhook, explícitamente v2/fuera de esta fase).

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `BudgetObligation::hasOne(PaymentOrder::class)` (`app/Models/BudgetObligation.php:61`) — plantilla directa de relación 1:1 a seguir para `IncomeRecord`↔`ExternalInvoiceReference`.
- `Voucher::hasOne(IncomeRecord::class)` (`app/Models/Voucher.php:66`) — mismo patrón `hasOne`, confirma que es la convención establecida del proyecto para relaciones 1:1.

### Established Patterns
- Filament Actions con label condicional según estado existente del registro relacionado ya no tienen precedente exacto en el código (no hay RelationManagers en el proyecto), pero es una extensión directa de `Tables\Actions\Action` estándar de Filament v5 — no requiere ningún patrón nuevo de arquitectura.
- Servicios de dominio con `handle()` único (`app/Services/Accounting/`) — si la creación/edición de la referencia requiere lógica no trivial (poco probable, dado que es un simple upsert de metadata), debe seguir esta convención en vez de lógica inline en la acción Filament.

### Integration Points
- `IncomeRecord` — punto de asociación 1:1 de la nueva referencia (D-06).
- `IncomeRecordsTable.php` — punto de inserción de la nueva acción (D-01/D-02).

</code_context>

<specifics>
## Specific Ideas

- Ninguna referencia específica adicional — el usuario confirmó las recomendaciones en cada pregunta sin desviaciones del alcance ya definido en el roadmap.

</specifics>

<deferred>
## Deferred Ideas

- **INVHOOK-04 (integración activa API/webhook):** ya reconocido como v2 en `.planning/REQUIREMENTS.md` — no discutido en profundidad porque está explícitamente fuera de esta fase.
- **Catálogo fijo de proveedores de facturación electrónica:** considerado como alternativa a D-04 y descartado — si en el futuro se necesita analítica por proveedor, esto se convierte en su propia mejora.
- **Validación de formato del CUFE:** considerada como alternativa a D-05 y descartada por fricción sin garantía real (esta fase no verifica nada contra DIAN) — revisar si algún cliente reporta CUFEs corruptos con frecuencia.
- **Múltiples referencias por IncomeRecord (hasMany):** considerada como alternativa a D-06 y descartada — el roadmap y las success criteria de la fase hablan de una referencia singular.

### Reviewed Todos (not folded)
None — no todos pendientes coincidieron con el alcance de esta fase.

</deferred>

---

*Phase: 04-hook-de-facturaci-n-externa-fase-e*
*Context gathered: 2026-09-17*

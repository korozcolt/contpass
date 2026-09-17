# Phase 1: Cotización electrónica (Fase A) - Context

**Gathered:** 2026-09-16
**Status:** Ready for planning

<domain>
## Phase Boundary

Permitir cotizar comercialmente a un tercero: crear una cotización con líneas de texto libre, moverla por su ciclo de vida (Borrador → Enviada → Aceptada/Rechazada, con Vencida calculada automáticamente por fecha de vigencia), generar un PDF, y convertir una cotización Aceptada en un `IncomeRecord` contable de forma auditable, idempotente y con numeración segura ante concurrencia. Sin validación DIAN, sin envío automático de correo/WhatsApp, sin firma electrónica del cliente — eso queda fuera de esta fase (ver roadmap).

</domain>

<decisions>
## Implementation Decisions

### Contenido y formato del PDF
- **D-01:** El PDF reusa el branding/logo existente de la empresa (mismo patrón que el resto del sistema, ya cubierto por `BrandingAssetsTest`).
- **D-02:** El PDF incluye una sección de notas/términos comerciales, alimentada por un campo de texto libre opcional en la cotización (ej. "50% anticipo, 50% contra entrega"). Se imprime solo si tiene contenido.
- **D-03:** Numeración consecutiva con formato `COT-2026-00001` (incluye el año) — facilita búsqueda/archivo a medida que crece el volumen entre años.

### Origen de las líneas de cotización
- **D-04:** Las líneas de cotización son de texto libre (descripción, cantidad, valor unitario) — NO se relacionan con el catálogo `WarehouseItem` de Almacén. Mantiene el módulo comercial desacoplado de Almacén y flexible para empresas de servicios que cotizan conceptos sin inventario físico.

### Cuentas contables en la cotización
- **D-05:** La cuenta de ingreso (clase 4) y la cuenta por cobrar (clase 13) se seleccionan como parte del formulario de la cotización, no al momento de convertir. La conversión a `IncomeRecord` es entonces un solo clic sin pedir datos adicionales, y el PDF/reporte puede mostrar la clasificación contable desde el borrador.

### Motivo de rechazo y vigencia
- **D-06:** Al marcar una cotización como Rechazada, se captura un campo de motivo obligatorio (texto breve, ej. "precio muy alto", "eligió otro proveedor") — útil para trazabilidad y reportes comerciales futuros.
- **D-07:** La vigencia por defecto es de 30 días desde la fecha de creación/envío, editable por cotización individual. Pasado ese plazo sin transición manual a Aceptada/Rechazada, el estado se calcula como Vencida (accessor, no una transición manual ni un job programado obligatorio para v1).

### Claude's Discretion
- Diseño exacto del layout del PDF (disposición de tabla de líneas, tipografía, espaciado) — dentro de lo que permite el branding existente.
- Mecanismo técnico para calcular "Vencida" (accessor computado en cada lectura vs. un job programado que actualiza el estado) — cualquiera es válido mientras el estado nunca requiera una transición manual del usuario.
- Estructura interna de `QuotationLine` (columnas exactas, además de descripción/cantidad/valor unitario/subtotal).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Alcance y decisiones ya tomadas
- `docs/roadmap-apolo.md` §"Plan de desarrollo: mejoras comerciales para mercado privado" → Fase A — alcance original, qué queda explícitamente fuera (DIAN, envío automático, firma electrónica)
- `.planning/PROJECT.md` — Core Value, constraints de arquitectura/testing/dependencias, Key Decisions
- `.planning/REQUIREMENTS.md` — QUOT-01 a QUOT-07 (requirements exactos de esta fase)

### Research de esta milestone (ya cubre Fase A en detalle)
- `.planning/research/ARCHITECTURE.md` §"Fase A — Quotation" — integración con `PostIncomeVoucher`, patrón de numeración, data flow, build order
- `.planning/research/PITFALLS.md` — Pitfall 1 (bug de enum en Filament v5 `$get()`), Pitfall 2 (`BuildVoucherNumber` no es company-scoped ni concurrency-safe, no clonar verbatim), Pitfall 3 (falta guard de idempotencia en conversión)
- `.planning/research/STACK.md` — recomendación `barryvdh/laravel-dompdf` ^3.1 para el PDF (dependencia nueva, requiere aprobación antes de instalar)
- `.planning/research/FEATURES.md` §Fase A — lifecycle de 4 estados, tabla de comparación con competidores

### Código fuente de referencia (patrones a reusar o corregir, no clonar ciegamente)
- `app/Services/Accounting/PostIncomeVoucher.php` — servicio a reusar tal cual desde la acción "Convertir a ingreso"
- `app/Services/Accounting/BuildVoucherNumber.php` — patrón de numeración con bug conocido (no company-scoped, no concurrency-safe) — `BuildQuotationNumber` debe corregir esto, no copiarlo
- `.planning/codebase/CONVENTIONS.md`, `.planning/codebase/STRUCTURE.md` — convenciones de Filament Resource (`Resource.php` + `Pages/` + `Schemas/` + `Tables/`)

### Bug conocido de Filament v5 (aplica a esta fase)
- `docs/roadmap-apolo.md` §"Registro de bugs" issue #1 — comparar `$get()` contra el caso del enum directamente, nunca contra `->value`, en cualquier campo condicional nuevo (ej. mostrar el campo de motivo solo cuando `status === QuotationStatus::Rejected`)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `PostIncomeVoucher::handle()` — servicio de dominio existente, la acción de conversión es un adaptador delgado que traduce campos de `Quotation`/`QuotationLine` a su forma de array esperada, sin duplicar lógica de asiento contable.
- Branding/logo existente (cubierto por `BrandingAssetsTest`) — reusar para el PDF.
- Catálogo de terceros (`ThirdParty`) y plan de cuentas (`ChartAccount`) ya existentes para los selects de la cotización.

### Established Patterns
- Recurso Filament estándar: `[Entity]Resource.php` + `Pages/` (Create/Edit/List/View) + `Schemas/` (formulario) + `Tables/` (columnas) — seguir esta estructura para `QuotationResource`.
- Servicios de dominio en `app/Services/{Domain}/` con método `handle()` — el numerador debe vivir en `app/Services/Accounting/BuildQuotationNumber.php` (o similar), no como lógica inline en el modelo/resource.
- No existe ningún patrón de generación de PDF en el código — se introduce desde cero con `barryvdh/laravel-dompdf` (pendiente de aprobación de dependencia).

### Integration Points
- Conversión a ingreso conecta con `PostIncomeVoucher::handle()` (existente, sin modificar su firma ni lógica interna).
- El formulario de la cotización usa los mismos catálogos ya existentes: `ThirdParty`, `ChartAccount` (cuenta de ingreso clase 4, cuenta por cobrar clase 13).

</code_context>

<specifics>
## Specific Ideas

- Formato de numeración específico solicitado: `COT-2026-00001` (prefijo + año + consecutivo de 5 dígitos).
- El campo de motivo de rechazo debe ser obligatorio cuando el estado pasa a Rechazada — es un ejemplo directo del bug conocido de Filament v5 (comparar contra `QuotationStatus::Rejected`, no `'rejected'` ni `->value`).
- Vigencia por defecto: 30 días desde creación/envío, editable por cotización.

</specifics>

<deferred>
## Deferred Ideas

- Insignia/aviso visual de cotizaciones próximas a vencer (dashboard badge) — ya está en REQUIREMENTS.md como QUOT-08, v2.
- Catálogo reusable de productos/servicios frecuentes para líneas — ya está en REQUIREMENTS.md como QUOT-09, v2. Se descartó explícitamente el acoplamiento con `WarehouseItem` para v1 (ver D-04).
- Envío automático de la cotización por correo/WhatsApp al marcarla "Enviada" — explícitamente fuera de esta fase (roadmap). "Enviada" es solo un cambio de estado manual; el usuario descarga y envía el PDF por fuera del sistema.

### Reviewed Todos (not folded)
Ninguno — no había todos pendientes que coincidieran con esta fase.

</deferred>

---

*Phase: 01-cotizaci-n-electr-nica-fase-a*
*Context gathered: 2026-09-16*

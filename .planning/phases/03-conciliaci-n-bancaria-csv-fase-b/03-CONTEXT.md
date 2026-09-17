# Phase 3: Conciliación bancaria CSV (Fase B) - Context

**Gathered:** 2026-09-17
**Status:** Ready for planning

<domain>
## Phase Boundary

Importar un extracto bancario en CSV, asociarlo a una `CashAccount`, proponer cruces contra `Payment` existentes (incluyendo el caso de una línea del extracto = suma de varios `Payment`, transferencias por lote), y requerir confirmación manual explícita de cada cruce antes de marcar `Payment.reconciled_at`. Ninguna reimportación de un período ya cubierto se duplica en silencio; ninguna fila no parseable se omite en silencio.

</domain>

<decisions>
## Implementation Decisions

### Formato del extracto CSV
- **D-01:** Perfiles por banco hardcoded en código (enum/config), no una tabla configurable en base de datos. Sin UI de gestión de perfiles — evita que esto se vuelva su propia mini-feature de administración.
- **D-02:** Alcance inicial: 2-3 bancos (Bancolombia + Davivienda, los más comunes según el research de mercado del milestone), seleccionables en un dropdown al subir el extracto.
- **D-03:** El CSV debe tener encabezados de columna obligatorios. El importador auto-detecta el delimitador (`,` o `;`, ambos comunes en extractos colombianos/Excel-es). Si faltan encabezados, se rechaza el archivo completo con mensaje claro.

### Detección de reimportación (BANKREC-03)
- **D-04:** El "período" se define por CashAccount + rango de fechas del propio archivo (fecha mínima/máxima de las filas). No se usa hash de archivo ni comparación fila por fila.
- **D-05:** Cualquier solape de fechas con un import previo para esa misma CashAccount rechaza el archivo COMPLETO — sin lógica de merge parcial ni importación de "solo filas nuevas". El usuario debe recortar su extracto para cubrir solo fechas nuevas antes de volver a subir.

### Motor de cruce (BANKREC-04)
- **D-06:** Cruce 1:1 usa: monto exacto (sin tolerancia numérica), ventana de fecha configurable (± N días, para cubrir transferencias/cheques que tardan en aplicarse), y referencia opcional. Coincidencia de monto+fecha+referencia = "alta confianza"; monto+fecha sin referencia = "candidato" — ambos casos requieren confirmación manual igual (ninguno se auto-confirma, por BANKREC-05).
- **D-07:** Transferencias por lote (una línea del extracto = suma de varios `Payment`): el sistema busca combinaciones automáticamente entre los `Payment` sin conciliar de esa `CashAccount` dentro de la misma ventana de fecha, y presenta la combinación como sugerencia agrupada para confirmación con un clic (o descarte).
- **D-08:** La búsqueda de combinaciones se limita a máximo 5 `Payment` candidatos por grupo (2^5 = 32 combinaciones máx. por línea del extracto) para mantener la búsqueda rápida y evitar falsos positivos por combinaciones triviales de montos pequeños.

### Flujo de revisión y confirmación (BANKREC-05)
- **D-09:** La revisión ocurre en una página Filament dedicada por import (patrón de página custom, como los reportes existentes en `app/Filament/Pages/`), no un Resource CRUD estándar con modal por fila. Lista las líneas del extracto de un import específico, cada una con su(s) candidato(s) propuesto(s) al lado, con un botón "Confirmar cruce" por fila/grupo.
- **D-10:** Al confirmar un cruce (simple o de lote), se marca `Payment.reconciled_at` de cada `Payment` involucrado — reusa el mecanismo ya existente (`is_reconciled` accessor/mutator), no se crea un campo nuevo de estado de conciliación.
- **D-11:** Líneas del extracto sin ningún candidato quedan visibles como "pendientes" indefinidamente tras la revisión — no bloquean el resto del import, y siguen apareciendo hasta que el usuario las cruce manualmente más adelante (ej. cuando se registre un `Payment` correspondiente después).

### Claude's Discretion
- Estructura exacta de las nuevas tablas/modelos (`BankStatementImport`, `BankStatementLine` o nombres equivalentes) — el usuario no fue consultado sobre nomenclatura de esquema, solo sobre comportamiento.
- Mecanismo exacto de auto-detección de delimitador CSV (heurística de conteo de caracteres vs. librería).
- Tamaño exacto de la ventana de fecha por defecto (± N días) — a definir en research/planning según patrones reales de extractos colombianos, dentro del rango "días, no semanas" implícito en la discusión.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Diseño original de Fase B
- `docs/roadmap-apolo.md` §"Fase B: Conciliación bancaria — importación de extractos" (líneas 144-153) — define el alcance base (algoritmo monto+fecha±tolerancia+referencia, reusar patrón de `ArchiveMasterPreviewImporter`, marcar `Payment.reconciled_at` al confirmar) y lo explícitamente excluido (Open Banking, OFX, conciliación asistida por IA). El caso de transferencias por lote (D-07/D-08) NO está en este documento original — es una ampliación de alcance que apareció en ROADMAP.md y se resolvió en esta discusión.

### Patrón de importador a reusar
- `app/Services/Imports/ArchiveMasterPreviewImporter.php` — patrón de referencia para parseo + reporte de filas rechazadas (`rejected: array<{reason}>`) sin omitir en silencio. Aplica directamente a BANKREC-02/06.

### Conciliación existente a reusar (no reinventar)
- `app/Services/Accounting/BankReconciliation.php` — servicio existente de saldo libros/conciliado/pendiente por `CashAccount`. Fase B debe integrarse con este servicio, no duplicar su lógica de cálculo de saldos.
- `app/Models/Payment.php` — `reconciled_at` (columna), `is_reconciled` (accessor/mutator virtual ya existente) — es el mecanismo de marca que Fase B debe reusar al confirmar un cruce (D-10).
- `app/Filament/Resources/Payments/Tables/PaymentsTable.php:24` — `ToggleColumn::make('is_reconciled')` ya existente para marca manual; Fase B añade el flujo asistido por CSV, no reemplaza el toggle manual.

### Requisitos y alcance excluido
- `.planning/REQUIREMENTS.md` §"Conciliación Bancaria por Extracto (BANKREC) — Fase B" — BANKREC-01 a BANKREC-06 (esta fase) y BANKREC-07 (bulk-confirm, explícitamente v2/fuera de esta fase).
- `.planning/REQUIREMENTS.md` §Out of Scope — "Conexión bancaria en vivo / Open Banking, soporte OFX" y "Conciliación bancaria asistida por IA" (conflicto directo con el Core Value de trazabilidad/auditabilidad — emparejamiento no determinístico).

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `BankReconciliation::pendingItems()` / `::summary()` — ya calcula partidas pendientes por `CashAccount`; la nueva página de revisión de Fase B puede alimentarse de una versión extendida de esta lógica en vez de reconstruir el cálculo de saldos.
- `Payment.reconciled` cast/accessor (`app/Models/Payment.php:45-46`) — mutador virtual que ya traduce booleano a `reconciled_at`; el flujo de confirmación de Fase B debe llamarlo, no escribir `reconciled_at` directamente.
- `ArchiveMasterPreviewImporter` — plantilla de manejo de transacción DB + colección de filas rechazadas con razón explícita, reusable como esqueleto para el nuevo importador de extractos.

### Established Patterns
- Páginas Filament custom para reportes/flujos no-CRUD viven en `app/Filament/Pages/` (ej. `LedgerReport`, `BankReconciliationReport`) — la página de revisión de cruces (D-09) debe seguir este patrón, no un Resource nuevo.
- Domain services en `app/Services/Accounting/` con método público único (`handle()` o nombre descriptivo) — el motor de cruce y el importador de extractos deben seguir esta convención, no lógica en la página Filament directamente.

### Integration Points
- `CashAccount` — el extracto se asocia a una `CashAccount` específica (BANKREC-01); el motor de cruce filtra `Payment` por `cash_account_id` igual que `BankReconciliation` ya hace.
- `Payment.reconciled_at` — punto de escritura final de todo el flujo de confirmación (D-10).

</code_context>

<specifics>
## Specific Ideas

- El research de mercado de este milestone (2026-09-16) identificó Bancolombia y Davivienda como los bancos más comunes entre clientes objetivo — de ahí la elección de esos 2 para los perfiles hardcoded (D-02).
- Límite de 5 Payments por combinación de lote (D-08) fue elegido explícitamente por el usuario para acotar el espacio de búsqueda combinatoria y evitar falsos positivos, no es un valor arbitrario de research.

</specifics>

<deferred>
## Deferred Ideas

- **BANKREC-07 (bulk-confirm):** confirmación masiva de un lote de cruces claramente correctos — ya reconocido como v2 en `.planning/REQUIREMENTS.md`. No se discutió en profundidad aquí porque está explícitamente fuera de esta fase.
- **Catálogo configurable de perfiles de banco (UI de administración):** considerado como alternativa a D-01/D-02 y descartado por ahora — si en el futuro se necesitan más de 2-3 bancos o el usuario quiere agregar bancos sin tocar código, esto se convierte en una fase/feature propia.
- **Importación con solo filas nuevas en solapes parciales:** considerado como alternativa a D-05 y descartado por complejidad/riesgo de falsos "duplicados nuevos" — si en el futuro el recorte manual de archivos resulta muy fricción para el usuario, revisar esta decisión.

### Reviewed Todos (not folded)
None — no todos pendientes coincidieron con el alcance de esta fase.

</deferred>

---

*Phase: 03-conciliaci-n-bancaria-csv-fase-b*
*Context gathered: 2026-09-17*

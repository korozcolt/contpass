# Phase 5: Exportación Excel (Fase D) - Context

**Gathered:** 2026-09-18
**Status:** Ready for planning

<domain>
## Phase Boundary

Exportar cada uno de los 6 reportes contables existentes (Libro Mayor, libro auxiliar, movimientos por tercero, balance de comprobación, cartera de clientes, cuentas por pagar) a `.xlsx`, además del CSV ya existente, con columnas de moneda y fecha como celdas nativas (numéricas/fecha, no texto), reusando la misma fuente de datos que el export CSV de cada reporte sin duplicar lógica de consulta.

</domain>

<decisions>
## Implementation Decisions

### Dependencia y motor de escritura
- **D-01:** Usar `openspout/openspout` directamente — ya está instalado como dependencia transitiva de `filament/actions` v5.6.8 (confirmado en `composer.lock`, v4.32). **Cero dependencia nueva que instalar ni aprobar.** Se descarta explícitamente instalar `maatwebsite/excel` (sería una dependencia nueva real).
- **D-02:** No usar el `Filament\Actions\Exports\Exporter`/`ExportAction` nativo de Filament v5 — confirmado por research que solo es compatible con 2 de los 6 reportes (los que usan `->query()`: `LedgerReport`, `ThirdPartyMovementsReport`); los otros 4 usan `->records()` sobre arrays/`DB::table()` sin `Builder` Eloquent, incompatibles con esa API. Además su pipeline XLSX es una reconversión CSV→XLSX, no un writer nativo tipado.

### Arquitectura
- **D-03:** La lógica de escritura Excel vive en **un servicio compartido** (ej. `app/Services/Reports/ExcelReportExporter.php` — nombre exacto a discreción de Claude), reusado por los 6 reportes. No un exporter separado por reporte.
- **D-04 (reuso de datos, XLSEXPORT-03):** Cada uno de los 6 métodos relevantes de `AccountingReportController` se refactoriza para extraer un **método privado compartido por reporte** (ej. `ledgerRows(Company $company, ...): array`) que arma headers+filas UNA sola vez. El método CSV existente y el nuevo método XLSX llaman ambos a ese método compartido — cumple "sin lógica de consulta duplicada" literalmente, no solo evitando duplicar la query sino también el mapeo a columnas.
- **D-05 (shape de filas):** El servicio Excel compartido acepta un shape **agnóstico**: `array<string> $headers` + `array<array<mixed>> $rows`, igual de simple que el array que hoy alimenta `fputcsv()`. Sin DTOs tipados por celda (`ExcelCell::currency()`, etc.) — se descartó por ser más código nuevo del que amerita una fase de 6 reportes.

### Formato de celdas
- **D-06 (moneda):** Celdas numéricas planas, sin símbolo de moneda ni formato especial aplicado vía estilo de openspout. El usuario puede sumarlas/ordenarlas/formatearlas él mismo en Excel.
- **D-07 (origen de fecha):** El método compartido (D-04) entrega el valor de fecha como **objeto Carbon crudo** en el array de filas (no como string ya formateado). El método CSV sigue formateando a string en su propio punto de salida (como hoy); el servicio Excel escribe el Carbon directamente como celda de fecha nativa — sin adivinar tipos por nombre/patrón de columna.
- **D-08 (formato de fecha visible):** `d/m/Y` (formato colombiano común) — el usuario prefirió esto explícitamente sobre el `Y-m-d` que usa el resto de la app (DatePickers Filament, CSV actual), priorizando familiaridad al abrir el archivo en Excel/LibreOffice sobre la consistencia interna de formato con el resto de la UI. **Nota para researcher/planner: esta es una desviación intencional del patrón `Y-m-d` usado en el resto de la app — no es un descuido.**

### Punto de entrada UI
- **D-09:** Cada una de las 6 páginas de reporte Filament obtiene un botón nuevo "Exportar Excel" (`Action::make('exportExcel')->url(...)`) **junto al** botón "Exportar CSV" ya existente — mismo patrón que el link actual, no un dropdown que reemplace ambos botones.
- **D-10 (mecanismo):** La descarga pasa por una **ruta nueva autenticada** en `routes/web.php` que apunta a un método nuevo en `AccountingReportController` (ej. `GET /accounting-reports/ledger.xlsx` junto a `/accounting-reports/ledger`), con el mismo gate `abort_unless($request->user() !== null, 403)` que ya usan las rutas CSV. No se usa una acción in-panel de Filament con `->action()`/`streamDownload()` — no hay precedente de ese patrón en el código y el mecanismo de ruta ya es consistente con el CSV existente.

### Claude's Discretion
- Nombre exacto de la clase/namespace del servicio Excel compartido (D-03).
- Nombres exactos de los métodos privados compartidos por reporte (D-04) y de las rutas/nombres de ruta `.xlsx`.
- Estilo visual de la hoja Excel más allá de lo decidido (negrita en encabezados, congelar primera fila, ancho de columna) — no discutido explícitamente, mantener mínimo salvo que sea trivial de añadir.
- Nombre del archivo de descarga (ej. `libro-auxiliar-2026-09-18.xlsx`).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Diseño original de Fase D
- `docs/roadmap-apolo.md` §"Fase D: Exportación Excel dedicada" (líneas 167-173) — define el alcance base y marca la aprobación de dependencia como pendiente (D-01/D-02 de este documento la resuelven: sin dependencia nueva).

### Requisitos y alcance excluido
- `.planning/REQUIREMENTS.md` §"Exportación Excel (XLSEXPORT) — Fase D" (líneas 41-45) — XLSEXPORT-01 a 03 (esta fase). XLSEXPORT-04 (línea 63, export multi-hoja resumen+detalle) es v2, explícitamente fuera de esta fase.

### Fuente de verdad actual del CSV (a refactorizar según D-04)
- `app/Http/Controllers/AccountingReportController.php` — controller con los 9 métodos CSV actuales vía `fputcsv()`/`downloadCsv()` (líneas 252-264 el helper compartido). Los 6 métodos relevantes a esta fase: `ledger()` (L29), `trialBalance()` (L70), `thirdPartyMovements()` (L84), `generalLedger()` (L168), `accountsReceivable()` (L204), `accountsPayable()` (L221). Los otros 3 métodos (`journal()`, `financialStatements()`, `bankReconciliation()`) NO están en el alcance de XLSEXPORT-01 — no tocar/exportar a Excel.
- `routes/web.php` (líneas 11-63) — define las 9 rutas actuales bajo `/accounting-reports/*`; agregar las 6 rutas `.xlsx` nuevas siguiendo el mismo patrón.

### Páginas Filament donde se agrega el botón (D-09)
- `app/Filament/Pages/LedgerReport.php` (header action CSV en L100-103), `ThirdPartyMovementsReport.php` (L88-91), `TrialBalanceReport.php` (L73-76), `GeneralLedgerReport.php` (L78-81), `AccountsReceivableReport.php` (L103-106), `AccountsPayableReport.php` (L108-111).

### Confirmación de dependencia ya vendorizada
- `composer.lock` — entrada `openspout/openspout` (confirmado v4.32, requerido por `filament/actions` v5.6.8 vía `composer why openspout/openspout`).

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AccountingReportController::downloadCsv()` (L252-264) — patrón de streaming existente a tomar como referencia (no reusar directamente, es CSV-específico) para el nuevo endpoint `.xlsx`.
- Los campos de moneda de los 6 reportes en scope ya llegan como float/decimal antes del punto de output (confirmado por research) — no requieren conversión de tipo para la celda numérica de Excel. (Nota: el único método que pre-formatea moneda a string con `number_format()` es `financialStatements()`, que está fuera de esta fase.)

### Established Patterns
- Servicios de dominio con `handle()` único (`app/Services/Accounting`, `app/Services/Budget`) — el servicio Excel compartido (D-03) puede o no seguir exactamente este patrón; al no ser lógica contable sino de presentación/export, el planner tiene discreción sobre el método de entrada exacto (`handle()`, `export()`, `write()`).

### Integration Points
- `AccountingReportController` — 6 métodos existentes a refactorizar (D-04) + 6 métodos nuevos `.xlsx`.
- `routes/web.php` — 6 rutas nuevas.
- 6 páginas Filament (`app/Filament/Pages/*Report.php`) — 6 header actions nuevas (D-09).

</code_context>

<specifics>
## Specific Ideas

- Ninguna referencia específica adicional — todas las decisiones quedaron capturadas en la sección de decisiones.

</specifics>

<deferred>
## Deferred Ideas

- **XLSEXPORT-04 (export multi-hoja resumen+detalle, ej. Libro Mayor):** ya reconocido como v2 en `.planning/REQUIREMENTS.md` — no discutido en profundidad, fuera de esta fase.
- **Instalar `maatwebsite/excel`:** considerado como alternativa a D-01 y descartado — dependencia nueva innecesaria cuando openspout ya está vendorizado y cubre lo requerido.
- **Formato de celda de moneda con símbolo COP:** considerado como alternativa a D-06 y descartado por fricción de implementación sin beneficio claro para esta fase.
- **DTOs tipados por celda (`ExcelCell::currency()`, etc.):** considerado como alternativa a D-05 y descartado — más código nuevo del que amerita el alcance de 6 reportes.
- **Acción in-panel de Filament (streaming directo) y dropdown único de export:** consideradas como alternativas a D-09/D-10 y descartadas — sin precedente en el código, el patrón de ruta + botón sibling ya es consistente con el CSV existente.
- **Exportar los 3 reportes CSV-only fuera de alcance** (`journal`, `financialStatements`, `bankReconciliation`): no forman parte de XLSEXPORT-01 (que enumera explícitamente 6 reportes) — no se tocan en esta fase.

### Reviewed Todos (not folded)
None — no había todos pendientes en el momento de esta discusión.

</deferred>

---

*Phase: 05-exportaci-n-excel-fase-d*
*Context gathered: 2026-09-18*

# Phase 5: Exportación Excel (Fase D) - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-18
**Phase:** 05-exportaci-n-excel-fase-d
**Areas discussed:** Dependencia y motor de escritura Excel, Punto de entrada del export en cada página, Reuso real de la fuente de datos (XLSEXPORT-03), Manejo de fechas nativas en Excel

---

## Dependencia y motor de escritura Excel

| Option | Description | Selected |
|--------|-------------|----------|
| openspout directo | Ya instalado transitivamente vía filament/actions (v4.32) — cero dependencia nueva, control total sobre tipos de celda | ✓ |
| Instalar maatwebsite/excel | API de alto nivel, pero dependencia nueva real, requiere aprobación y composer install | |

**User's choice:** openspout directo (Recomendado)
**Notes:** Confirma la aprobación explícita de dependencia que exige CLAUDE.md, resuelta sin instalación nueva.

| Option | Description | Selected |
|--------|-------------|----------|
| Un servicio compartido | Clase genérica que recibe headers+filas y escribe .xlsx con tipado nativo, reusable por los 6 reportes | ✓ |
| Un exporter por reporte | 6 clases separadas, cada una con su propio código de escritura Excel | |

**User's choice:** Un servicio compartido (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Numérico plano | Celda numérica simple, sin símbolo de moneda ni formato especial | ✓ |
| Formato moneda COP | Celda con formato de moneda vía estilo openspout, imitando el prefijo 'COP $' de los formularios | |

**User's choice:** Numérico plano (Recomendado)

---

## Punto de entrada del export en cada página

| Option | Description | Selected |
|--------|-------------|----------|
| Botón Excel junto al de CSV | Segundo Action::make('exportExcel') al lado del botón CSV actual, mismo patrón | ✓ |
| Dropdown único 'Exportar ▾' | Reemplaza ambos botones actuales por un ActionGroup | |

**User's choice:** Botón Excel junto al de CSV (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Ruta nueva en AccountingReportController | Ej. /accounting-reports/ledger.xlsx junto a la ruta CSV existente, mismo controller y gate | ✓ |
| Acción in-panel de Filament | ->action() con streamDownload() directo en el header action, sin precedente en el código | |

**User's choice:** Ruta nueva en AccountingReportController (Recomendado)

---

## Reuso real de la fuente de datos (XLSEXPORT-03)

| Option | Description | Selected |
|--------|-------------|----------|
| Extraer método compartido por reporte | Método privado que arma headers+filas UNA vez, reusado por CSV y XLSX — cumple XLSEXPORT-03 literalmente | ✓ |
| Mantener métodos actuales, XLSX reusa la misma query/servicio | La query no se duplica pero cada método re-mapea columnas a su manera | |

**User's choice:** Extraer método compartido por reporte (Recomendado)

| Option | Description | Selected |
|--------|-------------|----------|
| Array agnóstico: headers + rows | El servicio Excel recibe headers+filas ya armados, igual de simple que fputcsv() hoy | ✓ |
| Shape tipado común (DTOs por celda) | ExcelCell::currency()/date() por celda, más explícito pero más código nuevo | |

**User's choice:** Array agnóstico: headers + rows (Recomendado)

---

## Manejo de fechas nativas en Excel

| Option | Description | Selected |
|--------|-------------|----------|
| Y-m-d | Mismo formato que usa la UI Filament y el CSV actual — consistencia total | |
| d/m/Y (formato colombiano común) | Más familiar para un contador colombiano abriendo el archivo localmente, distinto al resto de la app | ✓ |

**User's choice:** d/m/Y (formato colombiano común)
**Notes:** Desviación intencional del recomendado (Y-m-d) — el usuario priorizó familiaridad local sobre consistencia interna de formato con el resto de la app. Documentado explícitamente en CONTEXT.md para que researcher/planner no lo traten como descuido.

| Option | Description | Selected |
|--------|-------------|----------|
| Carbon crudo en el array de filas | El método compartido deja el valor como objeto Carbon; CSV formatea a string al momento de fputcsv(), Excel lo escribe como celda de fecha nativa directamente | ✓ |
| String ya formateado, Excel lo re-parsea | El array sigue llevando fechas como string, Excel detecta columnas de fecha por nombre de header y reconvierte | |

**User's choice:** Carbon crudo en el array de filas (Recomendado)

---

## Claude's Discretion

- Nombre exacto de la clase/namespace del servicio Excel compartido
- Nombres exactos de los métodos privados compartidos por reporte y de las rutas `.xlsx`
- Estilo visual de la hoja Excel (negrita en encabezados, congelar fila, ancho de columna) — no discutido explícitamente
- Nombre del archivo de descarga

## Deferred Ideas

- XLSEXPORT-04 (export multi-hoja resumen+detalle) — v2, ya reconocido en REQUIREMENTS.md
- Los 3 reportes CSV-only fuera de alcance (journal, financialStatements, bankReconciliation) — no reciben Excel en esta fase

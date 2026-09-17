# Phase 4: Hook de facturación externa (Fase E) - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-17
**Phase:** 04-hook-de-facturaci-n-externa-fase-e
**Areas discussed:** Dónde y cómo se captura, Campos y su alcance, Validación del CUFE, Cardinalidad y correcciones

---

## Dónde y cómo se captura

| Option | Description | Selected |
|--------|-------------|----------|
| Acción dedicada en la tabla de IncomeRecords | Action "Registrar factura externa" en IncomeRecordsTable, modal | ✓ |
| Sección embebida en EditIncomeRecord | Bloque adicional en IncomeRecordForm | |

**User's choice:** Acción dedicada en la tabla de IncomeRecords.

| Option | Description | Selected |
|--------|-------------|----------|
| Sí, editable libremente | Corrección directa sin nota de ajuste | ✓ |
| Inmutable una vez capturada, requiere nueva referencia | Igual de estricto que comprobantes | |

**User's choice:** Sí, editable libremente.

---

## Campos y su alcance

| Option | Description | Selected |
|--------|-------------|----------|
| Texto libre | Input simple sin catálogo de proveedores | ✓ |
| Lista fija con opción "Otro" | Select con proveedores comunes + Otro | |

**User's choice:** Texto libre.

| Option | Description | Selected |
|--------|-------------|----------|
| Opcional | Solo número obligatorio | ✓ |
| Obligatoria junto con CUFE | Fuerza documento completo antes de registrar | |

**User's choice:** Opcional (URL del documento).

| Option | Description | Selected |
|--------|-------------|----------|
| Solo número de factura | CUFE y proveedor también opcionales | ✓ |
| Número + CUFE | Ambos obligatorios | |

**User's choice:** Solo número de factura.

---

## Validación del CUFE

| Option | Description | Selected |
|--------|-------------|----------|
| Sin validación de formato | Texto libre, coherente con "solo captura" | ✓ |
| Validar longitud/formato hex | Regla de 96 caracteres hexadecimales | |

**User's choice:** Sin validación de formato.

---

## Cardinalidad y correcciones

| Option | Description | Selected |
|--------|-------------|----------|
| Una sola (hasOne) | Coincide con roadmap y precedente BudgetObligation::hasOne(PaymentOrder) | ✓ |
| Múltiples (hasMany) | Permite factura + nota crédito externa | |

**User's choice:** Una sola (hasOne).

---

## Claude's Discretion

- Nombre exacto del modelo/tabla (`ExternalInvoiceReference` es sugerencia del roadmap, no decisión cerrada)
- Estructura exacta del modal/formulario Filament
- Convención de nombres de columna (inglés, siguiendo el resto del esquema)

## Deferred Ideas

- INVHOOK-04 (integración activa API/webhook) — ya reconocido como v2
- Catálogo fijo de proveedores de facturación electrónica — descartado por ahora
- Validación de formato del CUFE — descartada por fricción sin garantía real
- Múltiples referencias por IncomeRecord (hasMany) — descartado, roadmap habla de una referencia singular

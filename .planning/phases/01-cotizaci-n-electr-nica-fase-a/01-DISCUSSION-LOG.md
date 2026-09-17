# Phase 1: Cotización electrónica (Fase A) - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-16
**Phase:** 1-cotizacion-electronica-fase-a
**Areas discussed:** Contenido y formato del PDF, Origen de las líneas de cotización, Cuentas contables en la cotización, Motivo de rechazo y vigencia por defecto

---

## Contenido y formato del PDF

| Option | Description | Selected |
|--------|-------------|----------|
| Sí, reusar branding existente | Mismo logo/colores que el resto del sistema (BrandingAssetsTest) | ✓ |
| No, solo texto | PDF simple con nombre/NIT en texto, sin logo gráfico | |

**User's choice:** Sí, reusar branding existente.

| Option | Description | Selected |
|--------|-------------|----------|
| Campo de notas libre por cotización | Texto opcional que se imprime en el PDF si tiene contenido | ✓ |
| No incluir por ahora | Sin sección de términos | |

**User's choice:** Campo de notas libre por cotización.

| Option | Description | Selected |
|--------|-------------|----------|
| COT-00001 (simple) | Consecutivo sin año | |
| COT-2026-00001 (con año) | Incluye año para archivo/búsqueda | ✓ |

**User's choice:** COT-2026-00001 (con año).

**Notes:** Ninguna pregunta adicional — se pasó a la siguiente área.

---

## Origen de las líneas de cotización

| Option | Description | Selected |
|--------|-------------|----------|
| Texto libre (Recomendado) | Descripción manual, desacoplado de Almacén | ✓ |
| Reusar catálogo WarehouseItem | Selector desde WarehouseItem con opción de sobreescribir | |

**User's choice:** Texto libre.
**Notes:** Se descarta explícitamente el acoplamiento con Almacén para v1; queda como posible mejora futura (v2, QUOT-09 en REQUIREMENTS.md).

---

## Cuentas contables en la cotización

| Option | Description | Selected |
|--------|-------------|----------|
| Al crear la cotización (Recomendado) | Cuenta de ingreso y cuenta por cobrar en el formulario de la cotización | ✓ |
| Solo al momento de convertir | Formulario intermedio al hacer clic en "Convertir a ingreso" | |

**User's choice:** Al crear la cotización.
**Notes:** Prioriza que la conversión sea un solo clic sin pasos adicionales.

---

## Motivo de rechazo y vigencia por defecto

| Option | Description | Selected |
|--------|-------------|----------|
| Sí, campo de motivo obligatorio | Texto breve al rechazar | ✓ |
| No, solo el cambio de estado | Sin captura adicional | |

**User's choice:** Sí, campo de motivo obligatorio.

| Option | Description | Selected |
|--------|-------------|----------|
| 15 días por defecto, editable | Vigencia estándar corta | |
| 30 días por defecto, editable | Vigencia más larga | ✓ |

**User's choice:** 30 días por defecto, editable.

---

## Claude's Discretion

- Layout exacto del PDF (disposición de tabla, tipografía, espaciado).
- Mecanismo técnico para calcular el estado Vencida (accessor vs. job programado).
- Estructura interna exacta de `QuotationLine` más allá de descripción/cantidad/valor unitario/subtotal.

## Deferred Ideas

- Insignia de cotizaciones próximas a vencer (dashboard badge) — v2, ya en REQUIREMENTS.md (QUOT-08).
- Catálogo reusable de productos/servicios frecuentes para líneas — v2, ya en REQUIREMENTS.md (QUOT-09).
- Envío automático de la cotización por correo/WhatsApp — explícitamente fuera de alcance del milestone completo (docs/roadmap-apolo.md).

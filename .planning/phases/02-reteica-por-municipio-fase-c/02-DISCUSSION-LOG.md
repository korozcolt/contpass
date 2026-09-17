# Phase 2: ReteICA por municipio (Fase C) - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-17
**Phase:** 02-reteica-por-municipio-fase-c
**Areas discussed:** Catálogo de municipios, Discriminador de tipo de retención, Edición manual del municipio, Conflictos entre reglas ICA

---

## Catálogo de municipios — modelo y fuente de datos

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Estructura | Tabla única `municipalities` | Una fila por municipio con dept code/name embebidos | |
| Estructura | Dos tablas `departments` + `municipalities` | Normalizado, FK municipio→departamento | ✓ |
| Alcance datos | Catálogo DANE completo (~1122) | Los 32 departamentos + Bogotá, todos los municipios | ✓ |
| Alcance datos | Subconjunto curado para MVP | Solo departamentos donde opera el cliente actual | |
| Fuente | Investigar fuente oficial DANE/DIVIPOLA | El research/planner busca el listado oficial | ✓ |
| Fuente | Usuario provee el archivo | El usuario entrega un CSV/fuente concreta | |

**Notas:** Sin follow-up adicional — usuario confirmó pasar al siguiente tema tras estas 3 respuestas.

---

## Discriminador de tipo de retención (concept)

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Enum vs texto libre | Enum formal `WithholdingType` | Migra `concept` a enum ReteFuente/ReteIVA/ICA(+Otro), requiere migrar reglas existentes | ✓ |
| Enum vs texto libre | Mantener `concept` texto libre | Más rápido, filtra por patrón de texto — frágil | |
| Filtro ApplyWithholdingRules | Por tipo + municipio exacto | Solo aplica ICA cuyo municipio coincida con la operación; ReteFuente/ReteIVA sin cambios | ✓ |
| Filtro ApplyWithholdingRules | Solo una regla ICA sin importar municipio | Toma una regla ICA cualquiera activa — menos preciso | |

**Notas:** Usuario priorizó corrección/robustez (enum formal + filtro estricto) sobre velocidad de implementación, consistente con el Core Value de trazabilidad del proyecto.

---

## Mecanismo de edición manual del municipio de operación

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Dónde se edita | Por transacción, en causación de gasto | Campo de municipio en `ExpenseRecord`, precargado con domicilio de Company | ✓ |
| Dónde se edita | Configuración a nivel Company | Un solo municipio "activo" poco cambiante | |

**Notas:** El "qué" (default = domicilio Company, editable) ya estaba decidido desde el 2026-09-16 (research de mercado); esta pregunta solo resolvió el "dónde" del mecanismo de edición.

---

## Conflictos entre reglas ICA del mismo municipio

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Manejo de conflicto | Bloquear al guardar (validación de solapamiento) | Error en formulario, imposible crear el conflicto | ✓ |
| Manejo de conflicto | Permitir y aplicar la primera coincidencia | Comportamiento silencioso, igual al actual | |

**Notas:** Usuario explícitamente prefirió el bloqueo duro por ser consistente con el Core Value de trazabilidad/auditoría (no dejar ambigüedades silenciosas).

---

## Claude's Discretion

- Nombres exactos de tablas/columnas del catálogo (dentro del modelo normalizado departments+municipalities).
- Casos exactos del enum `WithholdingType` más allá de ReteFuente/ReteIVA/ICA.
- Mecanismo técnico de la validación de solapamiento (regla Filament vs service).
- Detalle de UX del select de municipio en `ExpenseRecord` (más allá de `searchable()->preload()` por convención).

## Deferred Ideas

- ReteICA por actividad económica (CIIU) del `ThirdParty` — RETICA-06, v2 (ya diferido desde antes de esta sesión).
- Reportes/analítica de retención ICA por municipio — RETICA-07, v2.
- Declaración/presentación de ICA ante la Secretaría de Hacienda municipal — fuera de alcance del roadmap completo.

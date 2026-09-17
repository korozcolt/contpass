# Phase 3: Conciliación bancaria CSV (Fase B) - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-17
**Phase:** 03-conciliaci-n-bancaria-csv-fase-b
**Areas discussed:** Formato del extracto CSV, Detección de reimportación, Motor de cruce y transferencias por lote, Flujo de revisión y confirmación

---

## Formato del extracto CSV

| Option | Description | Selected |
|--------|-------------|----------|
| Formato genérico único | Columnas fijas esperadas, usuario ajusta su CSV antes de subir | |
| Perfiles por banco | Bancolombia/Davivienda/BBVA con mapeo de columnas predefinido | ✓ |
| Mapeo manual por import | Usuario asigna columnas en cada import, sin perfiles guardados | |

**User's choice:** Perfiles por banco.
**Notes:** Follow-up para acotar alcance — ¿qué bancos y cómo se gestionan los perfiles?

| Option | Description | Selected |
|--------|-------------|----------|
| 2-3 bancos hardcoded, sin UI de gestión | Bancolombia + Davivienda en código, dropdown al subir | ✓ |
| Catálogo configurable en base de datos | Nueva tabla `bank_statement_profiles` editable desde Filament | |

**User's choice:** 2-3 bancos hardcoded, sin UI de gestión.

| Option | Description | Selected |
|--------|-------------|----------|
| Encabezados obligatorios, coma o punto y coma | Delimitador auto-detectado, encabezados ubican columnas | ✓ |
| Sin encabezados, posición fija de columna | Orden de columnas fijo sin depender de nombres | |

**User's choice:** Encabezados obligatorios, coma o punto y coma.

---

## Detección de reimportación

| Option | Description | Selected |
|--------|-------------|----------|
| Rango de fechas declarado + CashAccount | Rango tomado del propio archivo, rechazo si se superpone | ✓ |
| Hash del archivo completo | Simple pero no detecta mismo período con archivo ligeramente distinto | |
| Fila por fila | Más granular, permite reimportar agregando filas nuevas | |

**User's choice:** Rango de fechas declarado + CashAccount.

| Option | Description | Selected |
|--------|-------------|----------|
| Se rechaza el archivo completo | Cualquier solape de fechas rechaza todo el archivo | ✓ |
| Se importan solo las filas nuevas | Filtra automáticamente filas ya cubiertas | |

**User's choice:** Se rechaza el archivo completo.

---

## Motor de cruce y transferencias por lote

| Option | Description | Selected |
|--------|-------------|----------|
| Monto exacto + ventana de fecha ±N días + referencia opcional | Alta confianza si referencia coincide, candidato si no | ✓ |
| Solo monto + fecha exacta | Más estricto, menos candidatos automáticos | |

**User's choice:** Monto exacto + ventana de fecha ±N días + referencia opcional.

| Option | Description | Selected |
|--------|-------------|----------|
| Sistema sugiere combinaciones, usuario confirma | Busca combinaciones de Payments sin conciliar que sumen exacto | ✓ |
| Selección manual pura | Usuario selecciona manualmente 2+ Payments sin sugerencia automática | |

**User's choice:** Sistema sugiere combinaciones, usuario confirma.
**Notes:** Follow-up para acotar el espacio de búsqueda combinatoria.

| Option | Description | Selected |
|--------|-------------|----------|
| Máx. 5 Payments candidatos, misma ventana de fecha | Limita a 2^5=32 combinaciones máx. por línea | ✓ |
| Sin límite explícito | Puede ser lento o generar falsos positivos | |

**User's choice:** Máx. 5 Payments candidatos, misma ventana de fecha.

---

## Flujo de revisión y confirmación

| Option | Description | Selected |
|--------|-------------|----------|
| Página Filament dedicada por import | Lista líneas del extracto con candidatos al lado, botón por fila | ✓ |
| Acción de tabla en un Resource de extractos | RowAction con modal, más CRUD-estándar | |

**User's choice:** Página Filament dedicada por import.

| Option | Description | Selected |
|--------|-------------|----------|
| Quedan visibles como "pendientes" indefinidamente | No bloquea el resto del import, sigue apareciendo hasta cruce manual | ✓ |
| El usuario puede marcarla explícitamente como "ignorada" | Acción explícita de descarte sin borrar | |

**User's choice:** Quedan visibles como "pendientes" indefinidamente.

---

## Claude's Discretion

- Nomenclatura exacta de nuevas tablas/modelos (`BankStatementImport`, `BankStatementLine` o equivalentes)
- Mecanismo exacto de auto-detección de delimitador CSV
- Tamaño exacto de la ventana de fecha por defecto (± N días) — a definir en research/planning

## Deferred Ideas

- BANKREC-07 (bulk-confirm) — ya reconocido como v2, no discutido en profundidad
- Catálogo configurable de perfiles de banco (UI de administración) — descartado por ahora, revisar si se necesitan más de 2-3 bancos
- Importación con solo filas nuevas en solapes parciales — descartado por complejidad/riesgo, revisar si el recorte manual genera mucha fricción

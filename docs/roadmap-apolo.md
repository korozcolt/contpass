# Roadmap: Brecha de Funcionalidades vs. Apolo

## Contexto

ContPass se está desarrollando, en parte, a partir de un análisis comparativo contra una plataforma de referencia llamada **Apolo**: se identifican módulos y funcionalidades que Apolo tiene y ContPass no, y se implementan por fases.

Este documento existe porque, hasta el 2026-07-30, ese análisis y el historial de fases no estaban escritos en ningún lugar persistente — solo vivían en el contexto conversacional de sesiones de trabajo anteriores, y se perdieron con un `/clear`. Este archivo es el punto de partida para que eso no vuelva a pasar. Ver política en `AGENTS.md`/`CLAUDE.md` → "Documentation Files".

**Qué es Apolo:** Apolo Ultra (`apolo2022.apolosystemas.co`), el sistema de gestión financiera pública que hoy usa Aguas de Sucre S.A. E.S.P. (entidad AW052) — el cliente de referencia. Es 100% software de contabilidad pública colombiana (NICSP, presupuesto público, rendición a Contaduría/Contraloría/DIAN). El acceso dado a esta auditoría es de **usuario de consulta, solo lectura** — se ve la rama de Informes de cada módulo, no los formularios de captura de datos, así que puede haber más profundidad operativa detrás de cada reporte que no se relevó.

**Numeración de fases anteriores a 3b:** no reconstruible con certeza — no quedó registro explícito de si Almacén/Cuentas x Cobrar/Reportes/Firmantes fueron "fase 1/2/3a" o nombres propios. Se listan abajo por nombre de módulo, no por número, para no inventar un orden que no se confirmó.

## Auditoría de brecha — 2026-07-30

Relevamiento completo del menú de Apolo Ultra (11 categorías, 150 funciones de menú) comparado contra el código real de ContPass. Reporte visual: https://claude.ai/code/artifact/81b30bd4-1442-4b0f-b85b-2b0257a3cd12

**Cobertura global: 49 cubiertas (33%) · 31 parciales (21%) · 70 faltantes (47%)**

| Módulo | Cubierto | Parcial | Falta | Total |
|---|---|---|---|---|
| Almacén | 15 | 2 | 2 | 19 |
| Cuentas x Cobrar | 7 | 2 | 0 | 9 |
| Contabilidad | 8 | 4 | 8 | 20 |
| Presupuesto | 9 | 2 | 9 | 20 |
| Tesorería | 4 | 4 | 10 | 18 |
| Obligaciones | 2 | 4 | 4 | 10 |
| Nómina | 4 | 0 | 21 | 25 |
| Secretaría | 0 | 0 | 11 | 11 |
| Rendición | 0 | 10 | 0 | 10 |
| Definir Entidad | 0 | 3 | 5 | 8 |
| Herramientas | — | — | — | sin explorar (0 visibles con acceso de consulta) |

### Hallazgos nuevos (no estaban en el relevamiento anterior)

1. **Caja Menor** (Secretaría) — flujo completo de recibo/reembolso/auxiliar, distinto de `CashAccounts`.
2. **Depreciación de activos fijos** (Contabilidad) — ContPass no tiene ningún modelo de activo fijo.
3. **Clasificación de inversión pública** (Presupuesto) — Programático MGA, Sectores Presupuestales, Catálogo CCPET, ausentes por completo.
4. **Programación Anual de Caja (P.A.C.)** — módulo entero de flujo de caja proyectado, ausente.
5. **Estampillas** (Tesorería) — impuesto departamental/municipal, sin cobertura.
6. **Asimetría cartera vs. obligaciones** — "cuentas por edades" existe solo para cobrar (`AccountsReceivableReport`), no para pagar.
7. **17 áreas firmantes vs. 6** — `SignatoryArea` cubre 6 de las 17 áreas de Apolo (incluye 10 secretarías municipales) — relevante si el alcance incluye municipios, no solo ESP.
8. **Tipo de Entidad** — clasificación legal (Municipio/Establecimiento Público/ESE/ESP/IPS) ausente en `Company`; determina qué reportes de Rendición aplican a cada cliente.

### Orden sugerido (ponderado por riesgo legal + reuso de patrones existentes)

1. Tipo de Entidad — bajo esfuerzo, desbloquea saber qué Rendición aplica.
2. Libro Mayor + Conciliación Bancaria — mismo patrón de reportería ya probado.
3. Programación Anual de Caja (P.A.C.) — obligación de control fiscal frecuente.
4. Caja Menor — flujo acotado, uso operativo diario.
5. Cuentas por edades para Obligaciones — reutiliza el servicio de cartera ya construido.
6. MGA / CCPET / Sectores / Estampillas / Depreciación — evaluar caso por caso según cliente objetivo.
7. Secretaría (contratación) y motor de Nómina — módulos grandes, requieren alcance y confirmación de reglas antes de codear.

**Aún pendiente de confirmar con el usuario:** criterio final de priorización (el orden de arriba es una propuesta, no una decisión tomada).

## Estado de fases

> Las fases marcadas "(reconstruida)" se infirieron el 2026-07-30 a partir del estado del working tree (`git status`) y no de un registro explícito — falta que el usuario confirme número/nombre real de fase y orden.

### Completadas

- **Almacén (reconstruida)** — Catálogos `Warehouse`, `WarehouseItem`, `WarehouseMovement`/`WarehouseMovementLine`, reportes de auxiliar de elementos y stock.
- **Cuentas x Cobrar (reconstruida)** — Servicio `AccountsReceivable`, reporte de cartera de clientes.
- **Reportes contables adicionales (reconstruida)** — Balance general, estado de resultados, libro diario, centro de rendición de cuentas (`FinancialStatement` service + páginas Filament).
- **Firmantes y Dependencias (reconstruida)** — `CompanySignatory`, `Dependency`, códigos DANE en `Company`.
- **Fase 3b: Nómina (solo maestro)** — completada 2026-07-30. Catálogos `Employee`, `PayrollFund`, `PayrollConcept` (sin motor de cálculo). Ver detalle abajo.

### Pendiente / explícitamente fuera de alcance

- **Nómina — motor de cálculo**: liquidación mensual, aportes a salud/pensión/ARL, parafiscales, cesantías, prima, liquidación definitiva, vacaciones. Bloqueado hasta que el usuario confirme tasas y fórmulas exactas vigentes (21 de las 25 funciones del módulo).
- **Secretaría** (contratación, caja menor) — módulo completo sin ningún recurso todavía.
- El resto de brechas identificadas en la auditoría de 2026-07-30 (ver tabla de cobertura y orden sugerido más abajo).

## Fase 3b: Nómina (solo maestro) — detalle

**Objetivo:** catálogos base de nómina, sin ningún cálculo automático.

**Alcance:**
- `Employee` (empleados): identificación, cargo, dependencia, tipo de contrato, fondos de pensión/salud, fecha de ingreso/retiro, salario base.
- `PayrollFund` (fondos): EPS/AFP/ARL/cesantías, tipado por `PayrollFundType`.
- `PayrollConcept` (conceptos): catálogo de devengados/descuentos, tipado por `PayrollConceptType`.

**Explícitamente fuera:** cualquier cálculo (liquidación, aportes, cesantías, prima, liquidación definitiva, vacaciones).

**Archivos:** 3 migraciones, 3 enums (`PayrollFundType`, `PayrollConceptType`, `EmployeeContractType`), 3 modelos, 3 factories, 3 Filament Resources (`EmployeeResource`, `PayrollFundResource`, `PayrollConceptResource`), grupo de navegación "Nómina", `tests/Feature/EmployeeTest.php`.

**Verificación:** `vendor/bin/pint --dirty` limpio; suite completa 124/124 tests; probado manualmente en Herd (creación de Fondos vía UI, confirmado en base de datos; formularios de Conceptos y Empleados cargan correctamente).

## Registro de bugs

Los bugs se registran como GitHub Issues en `korozcolt/contpass`, no en este archivo. Este documento solo referencia el roadmap de fases/features.

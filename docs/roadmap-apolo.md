# Roadmap: Brecha de Funcionalidades vs. Apolo

## Contexto

ContPass se está desarrollando, en parte, a partir de un análisis comparativo contra una plataforma de referencia llamada **Apolo**: se identifican módulos y funcionalidades que Apolo tiene y ContPass no, y se implementan por fases.

Este documento existe porque, hasta el 2026-07-30, ese análisis y el historial de fases no estaban escritos en ningún lugar persistente — solo vivían en el contexto conversacional de sesiones de trabajo anteriores, y se perdieron con un `/clear`. Este archivo es el punto de partida para que eso no vuelva a pasar. Ver política en `AGENTS.md`/`CLAUDE.md` → "Documentation Files".

**Pendiente de completar con el usuario** (no reconstruible desde el código o el historial de git):

- Qué es Apolo exactamente (plataforma competidora, sistema de referencia del cliente, etc.).
- Lista completa de funcionalidades comparadas entre Apolo y ContPass.
- Numeración y orden real de las fases anteriores a la 3b (¿qué fue 1, 2, 3a?).
- Criterio de priorización de fases.

## Estado de fases

> Las fases marcadas "(reconstruida)" se infirieron el 2026-07-30 a partir del estado del working tree (`git status`) y no de un registro explícito — falta que el usuario confirme número/nombre real de fase y orden.

### Completadas

- **Almacén (reconstruida)** — Catálogos `Warehouse`, `WarehouseItem`, `WarehouseMovement`/`WarehouseMovementLine`, reportes de auxiliar de elementos y stock.
- **Cuentas x Cobrar (reconstruida)** — Servicio `AccountsReceivable`, reporte de cartera de clientes.
- **Reportes contables adicionales (reconstruida)** — Balance general, estado de resultados, libro diario, centro de rendición de cuentas (`FinancialStatement` service + páginas Filament).
- **Firmantes y Dependencias (reconstruida)** — `CompanySignatory`, `Dependency`, códigos DANE en `Company`.
- **Fase 3b: Nómina (solo maestro)** — completada 2026-07-30. Catálogos `Employee`, `PayrollFund`, `PayrollConcept` (sin motor de cálculo). Ver detalle abajo.

### Pendiente / explícitamente fuera de alcance

- **Nómina — motor de cálculo**: liquidación mensual, aportes a salud/pensión/ARL, parafiscales, cesantías, prima, liquidación definitiva, vacaciones. Bloqueado hasta que el usuario confirme tasas y fórmulas exactas vigentes (identificado como el módulo de mayor riesgo legal del relevamiento contra Apolo — 24 funciones).
- Todo lo demás que Apolo tenga y que aún no se ha comparado/documentado — **pendiente del listado completo del usuario**.

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

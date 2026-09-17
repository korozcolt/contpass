# ContPass

## What This Is

ContPass es una aplicación interna de control contable para empresas en Colombia (privadas y públicas/ESP), construida en Laravel 13 + Filament v5. Nació como MVP de contabilidad básica (causación, partida doble, retenciones, bancarización) y se extendió con módulos de Presupuesto Público, Tesorería, Almacén y Nómina (maestro), comparando su cobertura contra una plataforma pública de referencia llamada "Apolo" (cliente real: Aguas de Sucre S.A. E.S.P.).

## Core Value

Cada movimiento relevante produce un comprobante contable auditable e inmutable por partida doble — trazabilidad e inmutabilidad sobre conveniencia. Los comprobantes aprobados no se editan directamente; toda corrección pasa por una nota de ajuste con historial.

## Requirements

### Validated

- ✓ Contabilidad base: terceros con NIT/Cédula+DV validado (DIAN), Plan Único de Cuentas, caja/bancos (clase 11 PUC), reglas de retención versionadas por vigencia — existente
- ✓ Causación de ingresos y egresos con retenciones automáticas, comprobantes aprobados e inmutables, notas de ajuste, periodos contables con cierre — existente
- ✓ Reportes CSV: libro auxiliar, movimientos por tercero, balance de comprobación, balance general, estado de resultados, libro diario, libro mayor — existente
- ✓ Presupuesto Público: rubros de gasto/ingreso, CDP, RP, obligaciones presupuestales, traslados/modificaciones, órdenes de pago, ejecución presupuestal — existente
- ✓ Tesorería: Programación Anual de Caja (P.A.C.), Caja Menor (fondo fijo), conciliación bancaria ligera (marca manual, sin importación de extractos) — existente
- ✓ Almacén: catálogo de bodegas/ítems, movimientos con kardex, reportes de auxiliar de elementos y stock — existente
- ✓ Nómina: catálogo maestro (Employee/PayrollFund/PayrollConcept), sin motor de cálculo — existente
- ✓ Clasificación de entidad pública (`PublicEntityType`), firmantes y dependencias con códigos DANE, cuentas por edades (cartera y obligaciones) — existente
- ✓ 158 tests Pest pasando, 560 assertions, `vendor/bin/pint --dirty` limpio — verificado 2026-09-16

### Active

**Milestone: Mejoras Comerciales para Mercado Privado** (roadmap detallado y research de mercado en `docs/roadmap-apolo.md`):

- [ ] Fase A — Cotización electrónica: modelo `Quotation`/`QuotationLine`, PDF, conversión a `IncomeRecord`
- [ ] Fase C — ReteICA parametrizable por municipio (domicilio de `Company`, con opción de edición manual)
- [ ] Fase B — Conciliación bancaria por importación de extracto CSV, cruce automático contra `Payment`
- [ ] Fase E — Hook de datos para integración futura con facturador electrónico de terceros
- [ ] Fase D — Exportación Excel dedicada de los reportes existentes (requiere elegir y aprobar librería nueva)

### Out of Scope

- Facturación electrónica propia (DIAN/CUFE) — se integrará un proveedor tercero en una fase futura; este milestone solo deja el hook de datos (Fase E). Razón: certificación como PTO es un esfuerzo regulatorio grande, no vale la pena duplicarlo cuando existen proveedores especializados.
- Motor de cálculo de nómina electrónica — el mercado privado ya está bien servido (Alegra/Siigo/Helisa lo resuelven barato como addon). Razón: no competir de frente donde no hay ventaja diferencial; ContPass compite en rigor de trazabilidad/auditoría, no en nómina.
- Punto de venta (POS) — no encaja con la identidad de control/trazabilidad del producto.
- Conexión bancaria en vivo / Open Banking, soporte OFX, conciliación asistida por IA — fuera de esta fase de conciliación (Fase B); solo CSV con cruce manual/semiautomático.
- Generación/presentación de declaración de ICA ante la Secretaría de Hacienda municipal — Fase C solo calcula y registra la retención, no declara.
- ReteICA por actividad económica (CIIU) del `ThirdParty` — es el estándar real de mercado (Siigo/Siesa/SysCafé calculan por actividad × municipio del proveedor, no domicilio de la empresa pagadora), pero se descarta deliberadamente por simplicidad tras confirmarlo con el usuario (research 2026-09-16). Revisar si algún cliente concreto lo exige antes de construir Fase C.
- Motor de Nómina pública, módulo Secretaría/Contratación, MGA/CCPET, Estampillas, Depreciación de activos fijos, módulo de Suscriptores/Tarifas/PQRS para ESP — brechas conocidas contra Apolo, documentadas en `docs/roadmap-apolo.md`, pero fuera de este milestone (que es para mercado privado, no público).

## Context

- **Benchmark de referencia:** el desarrollo histórico de ContPass compara su cobertura contra "Apolo Ultra", software de gestión financiera pública que usa el cliente real Aguas de Sucre S.A. E.S.P. Auditoría completa de brecha en `docs/roadmap-apolo.md` (2026-07-30): 33% cubierto / 21% parcial / 47% faltante frente a 150 funciones de menú de Apolo.
- **Investigación de mercado (2026-09-16):** research con NotebookLM sobre competidores privados (Siigo, Alegra, World Office, Helisa, Loggro) y públicos (Sysman, Novasoft, PCT Enterprise, SYS Apolo, SIIF Nación), con precios reales de mercado (SaaS privado y contratos SECOP). Conclusiones completas y pricing recomendado en `docs/roadmap-apolo.md` → sección "Investigación de mercado y pricing". Notebook completo: https://notebooklm.google.com/notebook/9b55b11b-7003-4d6d-8f71-668aa34b3b0a
- **Codebase mapeado** en `.planning/codebase/` (STACK, INTEGRATIONS, ARCHITECTURE, STRUCTURE, CONVENTIONS, TESTING, CONCERNS) — leer antes de planear cualquier fase.
- **Documentación arquitectónica previa:** `docs/contpass-context.md` (servicios de dominio, modelo de datos, convenciones Filament, flujos operativos).
- **Bugs:** se registran como GitHub Issues en `korozcolt/contpass`, no en archivos de planning. Dos issues abiertos (ambos con fix ya en código): #1 (comparación de enum en campos condicionales de Filament v5) y #2 (`PaymentOrderFactory` con caso de enum inexistente).

## Constraints

- **Arquitectura**: la lógica contable vive en servicios de dominio (`app/Services/Accounting`, `app/Services/Budget`); los formularios Filament nunca crean asientos/registros directamente. Toda fase nueva debe seguir este patrón (ej. Fase A reusa `PostIncomeVoucher`).
- **Dependencias**: no se agregan ni cambian dependencias de Composer/NPM sin aprobación explícita del usuario (política del proyecto). Relevante para Fase D (librería de exportación Excel).
- **Estructura**: no se crean carpetas base nuevas sin aprobación; seguir la estructura de directorios existente (`app/Filament/Resources/{Entity}/`, `app/Services/{Domain}/`, etc.)
- **Testing**: todo cambio requiere test Pest nuevo o actualizado; `php artisan test --compact` debe pasar; `vendor/bin/pint --dirty --format agent` limpio antes de finalizar.
- **Idioma/locale**: español colombiano (`APP_LOCALE=es`, `APP_TIMEZONE=America/Bogota`) en toda la UI y datos de ejemplo.
- **Compatibilidad Filament v5**: cuidado con el bug de clase ya conocido — comparar `$get()` de un `Select` con `options(Enum::class)` contra el caso del enum directamente, no contra `->value` (ver issue #1).
- **CLAUDE.md del repo**: contiene las Laravel Boost guidelines checked-in — no debe sobreescribirse ni perderse al generar/refrescar documentación de proyecto.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| No facturación electrónica propia en este milestone | Certificación DIAN/PTO es un esfuerzo regulatorio grande; se integrará proveedor tercero después | — Pending |
| No motor de nómina electrónica ni POS en este milestone | Mercado privado ya bien servido por competidores ahí; ContPass compite en trazabilidad/auditoría | — Pending |
| ReteICA (Fase C): municipio se toma del domicilio de la `Company`, con opción de edición manual | Simplicidad sobre exactitud por-tercero; decisión de negocio del usuario. Research de mercado (2026-09-16) encontró que el estándar real (Siigo/Siesa/SysCafé) es tarifa por actividad económica CIIU × municipio del `ThirdParty`, no domicilio de `Company` — el usuario confirmó explícitamente mantener el modelo simple después de conocer ese tradeoff | — Pending |
| Out of scope explícito: ReteICA por actividad económica (CIIU) del `ThirdParty` | Más preciso y esperado por contadores acostumbrados a Siigo/Alegra, pero requiere nueva dimensión de datos en `ThirdParty` — descartado deliberadamente por simplicidad, no por desconocimiento | — Pending |
| Excel (Fase D): elegir librería por eficiencia y calidad, no por familiaridad previa | Usuario delegó el criterio técnico explícitamente; requiere aprobación de dependencia antes de instalar | — Pending |
| Orden de ejecución de fases: A → C → B → E → D | Priorizado por esfuerzo vs. valor comercial percibido (ver `docs/roadmap-apolo.md`) | — Pending |
| Estrategia de precios: ContPass privado post-mejoras ~$1.5M–$2.2M COP/año | Basado en research de mercado real (SECOP + SaaS privado); posiciona justo debajo de Alegra/Siigo/World Office compensado por rigor de auditoría | — Pending |
| Fase A (QUOT-04): aprobada dependencia nueva `barryvdh/laravel-dompdf` (~^3.1) | Genera el PDF de cotización desde vista Blade; confirmada no instalada por research (2026-09-16); usuario aprobó explícitamente durante `/gsd:plan-phase 1` (2026-09-16) | — Approved, pendiente `composer require` en fase de ejecución |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd:transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd:complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-09-16 after initialization*

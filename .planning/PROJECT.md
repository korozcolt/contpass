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
- ✓ Fase A — Cotización electrónica: modelo `Quotation`/`QuotationLine`, numeración segura company-scoped, ciclo de vida Borrador→Enviada→Aceptada/Rechazada/Vencida (calculada), PDF (`barryvdh/laravel-dompdf`), conversión idempotente a `IncomeRecord` reusando `PostIncomeVoucher` sin modificarlo — validado en Phase 1 (2026-09-17), 176 tests Pest pasando, QUOT-01 a QUOT-07 completos
- ✓ Fase C — ReteICA por municipio: catálogo DIVIPOLA (`departments`/`municipalities`, 33/1122 registros) construido desde cero, `WithholdingRule` extendida con `type`/`municipality_id` reusando el versionado existente (`scopeEffectiveOn`), bloqueo de reglas ICA solapadas por municipio (`EnsureNoOverlappingIcaRule`) y filtro exacto por municipio en `ApplyWithholdingRules` que deja ReteFuente/ReteIVA intactos por construcción — validado en Phase 2 (2026-09-17), 191 tests Pest pasando, RETICA-01 a RETICA-05 completos
- ✓ Fase B — Conciliación bancaria CSV: esquema `BankStatementImport`/`Line`/`Match` (D-01/D-02, perfiles Bancolombia/Davivienda hardcoded), `ImportBankStatement` reusando el patrón de `ArchiveMasterPreviewImporter` (encoding/delimitador vía `league/csv`, ya vendorizado — sin dependencia nueva), motor de cruce `ProposeBankStatementMatches` (1:1 + lote acotado a `match_pool_limit=10`) y `ConfirmBankStatementMatch` reusando `Payment.is_reconciled` sin duplicar estado, páginas Filament `UploadBankStatement`/`BankStatementReview` — validado en Phase 3 (2026-09-17), 226 tests Pest pasando, BANKREC-01 a BANKREC-06 completos
- ✓ Fase E — Hook de facturación externa: modelo `ExternalInvoiceReference` relacionado 1:1 (`hasOne`, siguiendo el precedente `BudgetObligation::hasOne(PaymentOrder::class)`) a `IncomeRecord`, captura manual vía acción de tabla "Registrar/Ver factura externa" en `IncomeRecordsTable` (número, CUFE sin validar formato, proveedor texto libre, URL documento), editable libremente sin nota de ajuste (metadata de referencia, no toca `Voucher`/`IncomeRecord`), sin ninguna llamada `Http::` — validado en Phase 4 (2026-09-18), 233 tests Pest pasando, INVHOOK-01 a INVHOOK-03 completos
- ✓ Fase D — Exportación Excel: servicio compartido `ExcelReportExporter` (celdas numéricas/fecha nativas vía `openspout/openspout`, ya vendorizado transitivamente — **cero dependencia nueva**, descartado `maatwebsite/excel`), 6 métodos privados `*Rows()` extraídos en `AccountingReportController` reusados por CSV y por los 6 nuevos endpoints `.xlsx` (mismo gate de autenticación que CSV), botón "Exportar Excel" junto al de CSV en las 6 páginas Filament de reporte — validado en Phase 5 (2026-09-18), 260 tests Pest pasando, XLSEXPORT-01 a XLSEXPORT-03 completos
- ✓ Fase 6 (gap closure, audit v1.0) — Limpieza de código huérfano ReteICA: eliminados `WithholdingRuleController`, `StoreWithholdingRuleRequest` y `resources/views/withholding-rules/*.blade.php` (código muerto pre-Filament sin ruta activa, referenciaba una columna `concept` ya eliminada); confirmado por grep que el `WithholdingRuleResource` de Filament es la única implementación real y quedó intacto — validado en Phase 6 (2026-09-18), 260 tests Pest pasando sin regresión, TECHDEBT-01 completo
- ✓ Fase 7 (gap closure, audit v1.0) — Cuentas por pagar mercado privado: `AccountsPayable::openItems()` ahora combina las obligaciones presupuestales públicas existentes (`budgetObligationItems()`, sin cambios) con `ExpenseRecord`s de mercado privado (`privateMarketItems()`, nuevo) usando el patrón `source_voucher_id` ya validado por el servicio hermano `AccountsReceivable`, monto neto de retención, columna "Obligación" con número de voucher; la lógica pública `payment_order_id` (bug pre-existente, nunca seteado — GitHub Issue #3) se dejó deliberadamente intacta y fuera de alcance — validado en Phase 7 (2026-09-19), 263 tests Pest pasando sin regresión, AP-01 completo
- ✓ Fase 8 (gap closure, audit v1.0) — Acceso a Cuentas por Pagar mercado privado: eliminado el override `canAccess()` de `AccountsPayableReport` que bloqueaba con 403 a empresas `has_budgetary_control = false` (ahora hereda el default de Filament, igual que la página hermana `AccountsReceivableReport`); copy de título/heading/empty-state neutralizado para no asumir exclusivamente "obligaciones presupuestales" — validado en Phase 8 (2026-09-19), 265 tests Pest pasando sin regresión, AP-01 cerrado end-to-end (servicio + CSV + Excel de Phase 7, acceso UI de Phase 8)

### Active

**Milestone: Mejoras Comerciales para Mercado Privado** — 5/5 fases originales + Phase 6, 7 y 8 (gap closure del audit v1.0) completas. Pendiente re-auditar (`/gsd:audit-milestone`) y ejecutar `/gsd:complete-milestone` para archivar.

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
| ReteICA (Fase C): municipio se toma del domicilio de la `Company`, con opción de edición manual | Simplicidad sobre exactitud por-tercero; decisión de negocio del usuario. Research de mercado (2026-09-16) encontró que el estándar real (Siigo/Siesa/SysCafé) es tarifa por actividad económica CIIU × municipio del `ThirdParty`, no domicilio de `Company` — el usuario confirmó explícitamente mantener el modelo simple después de conocer ese tradeoff | ✓ Aplicado, Phase 2 (2026-09-17) |
| Out of scope explícito: ReteICA por actividad económica (CIIU) del `ThirdParty` | Más preciso y esperado por contadores acostumbrados a Siigo/Alegra, pero requiere nueva dimensión de datos en `ThirdParty` — descartado deliberadamente por simplicidad, no por desconocimiento | ✓ Confirmado fuera de alcance, Phase 2 (2026-09-17) |
| Excel (Fase D): usar `openspout/openspout` directo en un servicio propio, no instalar `maatwebsite/excel` | El research de Phase 5 confirmó que openspout ya está vendorizado transitivamente vía `filament/actions` — resuelve la exigencia de aprobación de dependencia sin instalar nada nuevo; el `Exporter` nativo de Filament se descartó por ser incompatible con 4 de los 6 reportes (no usan `->query()` Eloquent) | ✓ Aplicado, Phase 5 (2026-09-18) |
| Orden de ejecución de fases: A → C → B → E → D | Priorizado por esfuerzo vs. valor comercial percibido (ver `docs/roadmap-apolo.md`) | ✓ Completado, Phase 5 (2026-09-18) |
| Estrategia de precios: ContPass privado post-mejoras ~$1.5M–$2.2M COP/año | Basado en research de mercado real (SECOP + SaaS privado); posiciona justo debajo de Alegra/Siigo/World Office compensado por rigor de auditoría | — Pending |
| Fase A (QUOT-04): aprobada dependencia nueva `barryvdh/laravel-dompdf` (~^3.1) | Genera el PDF de cotización desde vista Blade; confirmada no instalada por research (2026-09-16); usuario aprobó explícitamente durante `/gsd:plan-phase 1` (2026-09-16) | ✓ Instalada y validada, Phase 1 (2026-09-17), v3.1.2 |
| Fase A: índice único de `quotations.number` debe ser compuesto (`company_id`, `number`), no global | El plan-checker detectó que un índice global rompería la numeración "consecutiva por empresa" (QUOT-02) en cuanto dos compañías compartieran número en el mismo año; corregido antes de ejecutar | ✓ Aplicado, Phase 1 (2026-09-17) |
| Fase B: perfiles de banco (Bancolombia/Davivienda) hardcoded en código, sin UI de administración ni catálogo configurable | Usuario eligió acotar el alcance deliberadamente — evita que el formato CSV se convierta en su propia mini-feature; formato real de extracto no pudo verificarse (sin muestra), documentado como supuesto de mejor esfuerzo | ✓ Aplicado, Phase 3 (2026-09-17) |
| Fase B: transferencias por lote (N:1) se acotan a un pool de máx. 10 `Payment` candidatos antes de generar combinaciones de tamaño 2-5 | Evita búsqueda combinatoria costosa y falsos positivos; decisión explícita del usuario tras flag de research sobre el caso "más complejo de lo descrito originalmente" | ✓ Aplicado, Phase 3 (2026-09-17) |

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
*Last updated: 2026-09-19 after Phase 8 (Acceso a Cuentas por Pagar mercado privado — gap closure, audit v1.0) completion — milestone "Mejoras Comerciales para Mercado Privado" con 5/5 fases originales + Phases 6-7-8 (gap closure) completas; todo el gap closure del audit v1.0 cerrado, pendiente re-auditar y `/gsd:complete-milestone`*

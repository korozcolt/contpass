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

1. ~~Tipo de Entidad~~ — completado 2026-07-30 (ver "Estado de fases").
2. ~~Libro Mayor + Conciliación Bancaria~~ — completado 2026-07-30 (ver "Estado de fases").
3. ~~Programación Anual de Caja (P.A.C.)~~ — completado 2026-07-30 (ver "Estado de fases").
4. ~~Caja Menor~~ — completado 2026-07-30 (ver "Estado de fases").
5. ~~Cuentas por edades para Obligaciones~~ — completado 2026-07-30 (ver "Estado de fases").
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
- **Tipo de Entidad Pública** — completada 2026-07-30. Enum `PublicEntityType` (Municipio/Establecimiento Público/ESE/ESP/IPS), campo `public_entity_type` en `Company`, visible en Configuración solo cuando la Naturaleza es Pública. `AccountabilityCenter` (Rendición) muestra el contexto de la entidad actual. Sin gating automático de qué obligaciones de Rendición aplica cada tipo — pendiente confirmar esa regla.
- **Libro Mayor + Conciliación Bancaria (ligera)** — completada 2026-07-30. `FinancialStatement::generalLedger()` (saldo inicial/movimiento/saldo final por cuenta, todas las clases 1-7) + página "Libro mayor". `Payment.reconciled_at` con accessor virtual `is_reconciled` y `ToggleColumn` en Pagos; servicio `BankReconciliation` (saldo en libros/conciliado/pendiente) + página "Conciliación bancaria" listando partidas pendientes por cuenta de caja/banco. Sin importación de extractos bancarios — alcance ligero acordado con el usuario.
- **Programación Anual de Caja (P.A.C.)** — completada 2026-07-30. Modelo `CashProgramItem` (tabla `cash_program_items`): proyección mensual de caja desagregada por rubro presupuestal — `budget_appropriation_id` para gasto o `budget_revenue_id` para ingreso, tipada por enum `CashProgramMovementType` (Income/Expense). Accessors `executed_amount` (gasto: suma `Payment.amount` del mes vía cadena `paymentOrder→budgetObligation→budgetRegistration→budgetAvailabilityCertificate`; ingreso: suma `IncomeRecord.amount` del mes por `budget_revenue_id`) y `deviation` (proyectado − ejecutado). `CashProgramItemResource` (grupo de navegación "Tesorería", mismo guard `has_budgetary_control` que el resto de Presupuesto) con formulario condicional rubro-gasto/rubro-ingreso según tipo de movimiento y tabla con columnas Proyectado/Ejecutado/Desviación. Alcance acordado con el usuario: sin modificaciones/traslados de PAC durante el año (se edita directo el registro si cambia) — motor de ajustes con historial queda fuera de esta fase.
- **Caja Menor** — completada 2026-07-30. Modelos `PettyCashFund` (fondo: `employee_id` custodio opcional, `cash_account_id` cuenta asociada opcional, `authorized_amount` base fija) y `PettyCashMovement` (auxiliar: tipo `PettyCashMovementType` Opening/Expense/Replenishment/Closure, fecha, monto, beneficiario opcional vía `third_party_id`, soporte). Accessor `available_balance` en `PettyCashFund` = aperturas + reembolsos − gastos − cierres (ciclo de fondo fijo/imprest system estándar). `PettyCashFundResource` (grupo "Tesorería", sin guard de `has_budgetary_control` — igual que CashAccount/Payment, la caja menor no depende del control presupuestal) con `MovementsRelationManager` como pestaña de auxiliar bajo cada fondo. Alcance ligero: solo auxiliar administrativo, sin generación automática de comprobantes/asientos contables por movimiento — esa integración queda para una fase futura si se requiere.
- **Cuentas por edades para Obligaciones** — completada 2026-07-30. Servicio `AccountsPayable::openItems()` (`app/Services/Accounting/AccountsPayable.php`), simétrico a `AccountsReceivable`: agrega `BudgetObligation` no canceladas de la compañía, calcula `paid` sumando `Payment.amount` por `payment_order_id` (vía la relación `paymentOrder` de la obligación), `pending` = monto − pagado, y clasifica por antigüedad (`accrual_date` → hoy) en los mismos buckets que cartera de clientes: Corriente / 31-60 / 61-90 / +90 días. Página `AccountsPayableReport` (grupo "Reportes", mismo guard `has_budgetary_control`) y export CSV en `AccountingReportController::accountsPayable()` (`GET /accounting-reports/accounts-payable`) — reusa el patrón exacto de `AccountsReceivableReport`/`accountsReceivable()` mencionado en el hallazgo de auditoría (asimetría cartera vs. obligaciones, ítem 6).

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

## Investigación de mercado y pricing — 2026-09-16

Investigación de software similar (competidores privados y públicos) y precios de mercado, realizada con NotebookLM sobre las fuentes de este roadmap más ~85 fuentes web/SECOP nuevas. Notebook completo (fuentes, transcripciones y citas): https://notebooklm.google.com/notebook/9b55b11b-7003-4d6d-8f71-668aa34b3b0a

### Brechas identificadas frente a software comercial genérico (Siigo, Alegra, World Office, Helisa, Loggro)

- Facturación electrónica DIAN (CUFE) — ContPass no es Proveedor Tecnológico Autorizado, solo registra internamente. **Decisión tomada:** no se construye ahora; se deja para integración futura con un proveedor tercero (ver Fase E abajo).
- Motor de nómina electrónica (liquidación, aportes, DIAN) — ContPass solo tiene catálogo maestro (`Employee`/`PayrollFund`/`PayrollConcept`). **Decisión tomada:** no se prioriza para mercado privado — el mercado ya está bien servido por Alegra/Siigo/Helisa a bajo costo como addon; no vale la pena competir de frente ahí.
- Punto de venta (POS) / Documento Equivalente Electrónico. **Decisión tomada:** fuera de la identidad del producto (ContPass es control/trazabilidad, no punto de venta); no se prioriza.
- Conciliación bancaria con importación de extractos y asistencia por IA — ContPass solo tiene marca manual (`Payment.reconciled_at`). **Priorizado, ver Fase B.**
- ReteICA parametrizable por municipio — ContPass solo cubre retenciones nacionales por vigencia. **Priorizado, ver Fase C.**
- Cotizaciones/CRM comercial — ausente por completo. **Priorizado, ver Fase A.**
- Exportación Excel dedicada (ya listada como pendiente natural en `contpass-context.md`). **Priorizado, ver Fase D.**
- Multiempresa real y app móvil nativa — quedan fuera de esta ronda de mejoras; no son bloqueantes para vender a una pyme con una sola empresa activa.

### Brechas identificadas frente a software público (Sysman, Novasoft, PCT Enterprise, SYS Apolo) — complementan la tabla de brecha de Apolo ya documentada arriba

- Confirma lo ya sabido: motor de Nómina pública, Secretaría/Contratación, MGA/CCPET/Sectores, Estampillas, Depreciación de activos fijos.
- **Hallazgo nuevo, no relevado en la auditoría de Apolo original:** Sysman y SYS Apolo ofrecen a Empresas de Servicios Públicos (ESP) un módulo completo de **Suscriptores / Facturación tarifaria (CRA) / PQRS / Recaudo / Telemetría** — cálculo de tarifas por estrato/uso/municipio, indexación IPC/SMMLV, lecturas de medidores, reportes al SUI. ContPass no tiene ningún modelo para esto. Relevante porque el cliente de referencia (Aguas de Sucre S.A. E.S.P.) es exactamente ese tipo de entidad — pendiente decidir si se aborda en una fase futura, es un módulo grande.

### Precios de mercado encontrados (referencia, no son el pricing final de ContPass)

**Software privado genérico (SaaS, planes publicados):** Alegra $838.800–$3.358.800 COP/año, Siigo Nube $1.751.916–$2.494.425 COP/año, World Office Cloud $680.000–$2.193.000 COP/año, Loggro $1.307.880–$3.359.880 COP/año, Helisa Cloud $2.313.360–$4.762.800 COP/año — todos incluyen facturación electrónica nativa.

**Software público (contratos SECOP/Colombia Compra Eficiente reales):** soporte/actualización anual en entidades pequeñas $11.1M–$19.8M COP (Sysman en Cajicá/Metrolínea), licencia completa en E.S.E. $34M COP/año (SYS Apolo en Hospital San Carlos), submódulo único de Almacén $50.8M COP/año (PCT Enterprise en Min. Justicia), nómina SaaS 100 empleados $69.9M COP/año (Novasoft en ICANH), suite completa + hosting en municipio ~$307M COP/año anualizado (Sysman en Acacías).

**Conclusión:** el mercado público paga entre 4 y 15 veces más que el mercado privado genérico por software equivalente — confirma que la estrategia de especialización en sector público/ESP que ya se viene siguiendo es la correcta comercialmente, no solo técnicamente.

### Pricing recomendado

- **ContPass hoy, vendido a entidad pública/ESP** (33% de cobertura vs. Apolo, sin nómina ni secretaría): **$6M–$12M COP/año.**
- **ContPass público, cerrando Nómina + Secretaría** (~60-70% de cobertura): **$18M–$35M COP/año** — nivel SYS Apolo/Sysman en entidades medianas.
- **ContPass privado, HOY (sin las mejoras de esta fase):** $850.000–$1.400.000 COP/año — por debajo de cualquier competidor porque aún no tiene cotizaciones ni conciliación asistida.
- **ContPass privado, DESPUÉS de las Fases A–E de este plan** (cotización electrónica, conciliación con extractos, ReteICA municipal, Excel, hook de facturación externa — pero sin emisión propia de factura DIAN): **$1.500.000–$2.200.000 COP/año** (~$125.000–$183.000/mes). Se posiciona justo debajo de Alegra Pyme ($1.798.800/año), Siigo Emprendedor ($2.152.425/año) y World Office Pyme Plus ($2.040.000/año) — que sí incluyen facturación nativa —, compensado por el rigor de trazabilidad/inmutabilidad de comprobantes que ninguno de ellos ofrece de fábrica. Sumando un facturador externo económico como partner (~$120.000–$300.000 COP/año), el costo total para el cliente ($1.62M–$2.5M) sigue quedando igual o por debajo del "todo incluido" de los rivales.

## Plan de desarrollo: mejoras comerciales para mercado privado

Priorizado por esfuerzo (reuso de patrones/servicios existentes) vs. valor comercial percibido, derivado de la investigación de mercado de arriba. Ninguna fase requiere facturación electrónica propia — esa integración queda fuera de alcance hasta que se decida qué proveedor tercero usar.

### Fase A: Cotización electrónica (comercial)

**Objetivo:** permitir generar y enviar cotizaciones a terceros, sin validación DIAN (no es factura ni documento equivalente), y convertirlas en un `IncomeRecord` cuando el cliente acepta.

**Alcance:**
- Modelo `Quotation` (tercero, fecha, validez, estado: borrador/enviada/aceptada/rechazada/vencida, notas) y `QuotationLine` (descripción, cantidad, valor unitario, subtotal).
- `QuotationResource` (grupo "Operación"), PDF de la cotización, acción "Convertir a ingreso" que crea el `IncomeRecord` correspondiente reusando `PostIncomeVoucher`.
- Numeración consecutiva por empresa (mismo patrón que `BuildVoucherNumber`/`BuildWarehouseMovementNumber`).

**Explícitamente fuera:** validación DIAN, envío automático de correo/WhatsApp, firma electrónica del cliente.

### Fase B: Conciliación bancaria — importación de extractos

**Objetivo:** importar extractos bancarios (CSV) y cruzarlos contra `Payment` para reducir el trabajo manual de conciliación.

**Alcance:**
- Importador de extracto (mismo patrón que `ArchiveMasterPreviewImporter`): parseo de CSV, tabla de líneas importadas en estado "pendiente de cruce".
- Algoritmo de cruce por monto + fecha (± tolerancia) + referencia contra `Payment` de la `CashAccount` correspondiente; UI de confirmación manual para los que no calzan automáticamente.
- Al confirmar un cruce, marca `Payment.reconciled_at` (reusa lo que ya existe en `BankReconciliation`).

**Explícitamente fuera:** conexión bancaria en vivo (Open Banking/APIs de bancos), formato OFX (solo CSV en esta fase), conciliación asistida por IA.

### Fase C: ReteICA parametrizable por municipio

**Objetivo:** calcular automáticamente ReteICA según el municipio de la operación, igual que ya se hace con retefuente/reteIVA.

**Alcance:**
- Extender `WithholdingRule` (o tabla relacionada) con dimensión de municipio, reusando el catálogo DANE ya existente para `Dependency`/`CompanySignatory`.
- `ApplyWithholdingRules` calcula la tarifa de ICA vigente según el municipio configurado en `Company` (o del tercero, a definir con el usuario).

**Explícitamente fuera:** generación/presentación de la declaración de ICA ante la Secretaría de Hacienda municipal — solo el cálculo y registro contable de la retención.

**Pendiente de confirmar con el usuario:** si el municipio relevante es el de la `Company` (domicilio fiscal) o el del `ThirdParty` (lugar de la operación) — afecta el diseño del dato.

### Fase D: Exportación Excel dedicada

**Objetivo:** exportar los reportes existentes (Libro auxiliar, movimientos por tercero, balance de comprobación, cartera, cuentas por pagar, libro mayor) en `.xlsx` además de CSV.

**Alcance:** acción de exportación Excel en cada página de reporte Filament ya existente, mismo contenido que el CSV actual pero con formato de celda (moneda, fecha) nativo de Excel.

**Requiere aprobación de dependencia nueva** (p. ej. `maatwebsite/excel` o `openspout/openspout`) — pendiente de confirmar con el usuario antes de instalar, según política del proyecto ("no cambiar dependencias sin aprobación").

### Fase E: Hook de integración con facturador electrónico de terceros

**Objetivo:** dejar preparado el modelo de datos para recibir la referencia de una factura electrónica emitida por un proveedor externo (Siigo Facturación, Alegra Facturación, Factus, etc.), sin emitir facturación propia.

**Alcance:** campos nullable en `IncomeRecord` (o modelo `ExternalInvoiceReference` relacionado 1:1) para número de factura externo, CUFE, proveedor y URL/PDF del documento; captura manual desde el formulario Filament de Ingresos.

**Explícitamente fuera:** integración activa por API/webhook con un proveedor específico — se define en una fase futura cuando el usuario elija el proveedor tercero definitivo.

### Orden sugerido de ejecución

1. Fase A (Cotización) — mayor valor comercial percibido, menor esfuerzo.
2. Fase C (ReteICA) — bajo esfuerzo, reusa patrón existente, cierra riesgo de cumplimiento.
3. Fase B (Conciliación con extractos) — esfuerzo medio, mayor impacto en uso diario del contador.
4. Fase E (Hook facturación externa) — esfuerzo mínimo, prepara terreno comercial.
5. Fase D (Excel) — depende de aprobación de dependencia nueva; puede ejecutarse en paralelo a cualquiera de las anteriores una vez aprobada.

**Aún pendiente de confirmar con el usuario:** aprobación de la dependencia de exportación Excel (Fase D), y el criterio de municipio para ReteICA (Fase C).

## Registro de bugs

Los bugs se registran como GitHub Issues en `korozcolt/contpass`, no en este archivo. Este documento solo referencia el roadmap de fases/features.

**Bug de clase encontrado 2026-07-30** ([issue #1](https://github.com/korozcolt/contpass/issues/1)): en Filament v5, cuando un `Select` usa `->options(EnumClass::class)`, `$get()` dentro de un closure `visible()`/`required()` devuelve la instancia del enum, no su `->value`. Comparar contra `->value` (`$get('type') === Enum::Case->value`) es siempre `false`. Afectaba 3 formularios — `WarehouseMovementForm` (3 campos: almacén destino, dependencia destino, proveedor), `BudgetModificationForm` y `BudgetModificationsRelationManager` (rubro origen en traslados) — donde el campo condicional nunca se mostraba en el navegador aunque los tests a nivel de modelo pasaran. Corregido comparando contra el caso del enum directamente (`=== Enum::Case`). Ver commit del fix.

**Bug encontrado 2026-07-30** ([issue #2](https://github.com/korozcolt/contpass/issues/2)): `PaymentOrderFactory` asignaba `'method' => PaymentMethod::Transfer`, caso inexistente en el enum (`App\Enums\PaymentMethod` solo tiene `BankTransfer`). Cualquier test que usara `PaymentOrder::factory()` sin sobreescribir `method` fallaba con `Undefined constant`. Encontrado al construir `CashProgramItemTest` (P.A.C.), que encadena `PaymentOrder::factory()` para probar el cálculo de `executed_amount` de gasto. Corregido a `PaymentMethod::BankTransfer` en el mismo commit.

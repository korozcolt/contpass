# Roadmap: ContPass — Mejoras Comerciales para Mercado Privado

## Overview

Cinco features aditivas al monolito Laravel 13 + Filament v5 existente, dirigidas a cerrar brecha comercial contra competidores de mercado privado (Siigo, Alegra, World Office) sin comprometer el Core Value de trazabilidad/inmutabilidad contable. Ninguna fase depende técnicamente de otra — el orden A→C→B→E→D es una priorización de valor/esfuerzo confirmada por research de arquitectura (2026-09-16), no una cadena de dependencias de código. Se ejecuta: Cotización electrónica → ReteICA por municipio → Conciliación bancaria CSV → Hook de facturación externa → Exportación Excel. Las Phases 6 y 7 son cierre de gaps no-críticos identificados por `/gsd:audit-milestone` sobre la v1.0 (ver `.planning/v1.0-MILESTONE-AUDIT.md`), no fases del scope original.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

- [x] **Phase 1: Cotización electrónica (Fase A)** - Cotizar, transicionar estados y convertir a ingreso sin duplicados
- [x] **Phase 2: ReteICA por municipio (Fase C)** - Retención ICA parametrizable por municipio sin acumulación entre municipios
- [x] **Phase 3: Conciliación bancaria CSV (Fase B)** - Importar extracto bancario y proponer cruces contra pagos sin duplicar ni perder filas
- [x] **Phase 4: Hook de facturación externa (Fase E)** - Capturar referencia de factura electrónica de terceros, sin integración activa
- [x] **Phase 5: Exportación Excel (Fase D)** - Exportar los 6 reportes existentes a .xlsx con celdas nativas
- [x] **Phase 6: Limpieza de código huérfano ReteICA** - Eliminar `WithholdingRuleController`/request/vistas blade sin ruta, huérfanos desde antes del `WithholdingRuleResource` de Filament
- [ ] **Phase 7: Cuentas por pagar — alcance mercado privado** - Extender el reporte "cuentas por pagar" para incluir `ExpenseRecord`s de mercado privado, no solo `BudgetObligation`

## Phase Details

### Phase 1: Cotización electrónica (Fase A)
**Goal**: Usuario puede cotizar a un tercero, mover la cotización por su ciclo de vida y convertirla en un comprobante de ingreso auditable, sin duplicados de numeración ni de conversión.
**Depends on**: Nothing (first phase; no dependencia técnica con otras fases del milestone)
**Requirements**: QUOT-01, QUOT-02, QUOT-03, QUOT-04, QUOT-05, QUOT-06, QUOT-07
**Success Criteria** (what must be TRUE):
  1. Usuario puede crear una cotización para un tercero con una o más líneas (descripción, cantidad, valor unitario) y ver subtotal/total calculado
  2. La numeración de cotizaciones no produce duplicados bajo creación concurrente (consecutiva por empresa, segura ante condición de carrera)
  3. Usuario puede transicionar la cotización Borrador → Enviada → Aceptada/Rechazada; el estado Vencida se calcula automáticamente por fecha de validez, nunca es una transición manual
  4. Usuario puede generar un PDF de la cotización con datos de la empresa, líneas, subtotal/total y fecha de validez
  5. Convertir una cotización Aceptada a `IncomeRecord` reusa `PostIncomeVoucher` en una sola acción; convertir la misma cotización dos veces nunca crea un segundo comprobante (idempotente en la misma transacción), y la cotización convertida queda de solo lectura con enlace a su comprobante
**Plans**: 4/4 plans complete
Plans:
- [x] 01-01-PLAN.md — Fundamento de dominio: migraciones, enum QuotationStatus, modelos Quotation/QuotationLine
- [x] 01-02-PLAN.md — Servicios: BuildQuotationNumber (numeración segura) + ConvertQuotationToIncome (conversión idempotente)
- [x] 01-03-PLAN.md — Recurso Filament QuotationResource: formulario, ciclo de vida, conversión
- [x] 01-04-PLAN.md — PDF de cotización (barryvdh/laravel-dompdf)
**UI hint**: yes

### Phase 2: ReteICA por municipio (Fase C)
**Goal**: Usuario puede configurar y aplicar retención ICA parametrizada por municipio, sin que se acumulen retenciones de más de un municipio en una misma transacción.
**Depends on**: Nothing técnicamente (sigue a Phase 1 solo por orden de ejecución priorizado, no por dependencia de código)
**Requirements**: RETICA-01, RETICA-02, RETICA-03, RETICA-04, RETICA-05
**Success Criteria** (what must be TRUE):
  1. Existe un catálogo de municipios colombianos (códigos DANE de departamento/municipio + nombres), construido desde cero, seleccionable en la aplicación
  2. Usuario puede configurar una regla de retención ICA con vigencia, tarifa y base mínima propias ligada a un municipio específico, reusando el versionado existente de `WithholdingRule`
  3. El municipio usado para seleccionar la regla de ICA aplicable toma por defecto el domicilio registrado de la `Company`, con opción de edición manual
  4. Con dos reglas de ICA activas para distintos municipios, solo se aplica la regla que coincide con el municipio de la operación — nunca se acumulan retenciones de más de un municipio en la misma transacción
  5. Las retenciones nacionales existentes (ReteFuente, ReteIVA) siguen aplicándose sin verse afectadas por la nueva dimensión de municipio
**Plans**: 3/3 plans complete
Plans:
- [x] 02-01-PLAN.md — Catálogo DIVIPOLA (departments/municipalities) + enum WithholdingType
- [x] 02-02-PLAN.md — WithholdingRule: type/municipio + validación de solapamiento ICA (D-08) + UI
- [x] 02-03-PLAN.md — Filtro de municipio en ApplyWithholdingRules + municipio de la operación en ExpenseRecord
**UI hint**: yes

### Phase 3: Conciliación bancaria CSV (Fase B)
**Goal**: Usuario puede importar un extracto bancario en CSV y conciliarlo contra pagos existentes sin duplicar importaciones, perder filas en silencio, ni confirmar cruces automáticamente.
**Depends on**: Nothing técnicamente (sigue a Phase 2 solo por orden de ejecución priorizado, no por dependencia de código)
**Requirements**: BANKREC-01, BANKREC-02, BANKREC-03, BANKREC-04, BANKREC-05, BANKREC-06
**Success Criteria** (what must be TRUE):
  1. Usuario puede subir un archivo de extracto bancario en CSV asociado a una `CashAccount` específica
  2. El importador normaliza codificación de caracteres (Windows-1252/UTF-8) y formatos de fecha comunes en extractos colombianos, y muestra explícitamente como fila rechazada cualquier línea que no pueda parsear (codificación inválida, fecha no reconocible) en vez de omitirla en silencio
  3. Reimportar un periodo de extracto ya importado se detecta y se rechaza/marca explícitamente, nunca se duplica en silencio
  4. El sistema propone cruces entre líneas del extracto y `Payment` existentes usando monto, ventana de fecha y referencia, incluyendo el caso donde una línea del extracto corresponde a la suma de varios `Payment` (transferencias por lote)
  5. Usuario debe confirmar explícitamente cada cruce propuesto antes de que un `Payment` se marque como conciliado — ningún cruce se confirma automáticamente
**Plans**: 4/4 plans complete
Plans:
- [x] 03-01-PLAN.md — Fundamento: enums (BankProfile/estados), migraciones y modelos (BankStatementImport/Line/Match)
- [x] 03-02-PLAN.md — ImportBankStatement: encoding/delimitador, encabezados, período solapado, rechazo fila-por-fila (TDD)
- [x] 03-03-PLAN.md — ProposeBankStatementMatches (1:1 + lote acotado) y ConfirmBankStatementMatch (TDD)
- [x] 03-04-PLAN.md — Páginas Filament UploadBankStatement + BankStatementReview (D-09)
**UI hint**: yes

### Phase 4: Hook de facturación externa (Fase E)
**Goal**: Usuario puede dejar registro auditable de una factura electrónica emitida por un proveedor tercero, sin que el sistema intente integrarse activamente ni comprometa la inmutabilidad del comprobante de ingreso.
**Depends on**: Nothing técnicamente (sigue a Phase 3 solo por orden de ejecución priorizado, no por dependencia de código)
**Requirements**: INVHOOK-01, INVHOOK-02, INVHOOK-03
**Success Criteria** (what must be TRUE):
  1. Usuario puede registrar manualmente una referencia de factura electrónica externa (número, CUFE, proveedor, URL del documento) asociada a un `IncomeRecord` ya creado
  2. La referencia de factura externa se guarda en un registro relacionado propio, sin modificar el `IncomeRecord` inmutable
  3. Ninguna parte del sistema intenta llamar a una API externa de facturación usando estos campos — solo captura, sin integración activa
**Plans**: 1/1 plans complete
Plans:
- [x] 04-01-PLAN.md — Modelo ExternalInvoiceReference (hasOne) + acción de tabla en IncomeRecordsTable
**UI hint**: yes

### Phase 5: Exportación Excel (Fase D)
**Goal**: Usuario puede exportar cualquiera de los reportes contables existentes a un archivo Excel correctamente tipado, junto al CSV ya existente.
**Depends on**: Nothing técnicamente; único gate real es aprobación de dependencia explícita (`openspout/openspout`, ya vendorizada transitivamente — no es una instalación nueva). Sigue a Phase 4 por orden de ejecución priorizado.
**Requirements**: XLSEXPORT-01, XLSEXPORT-02, XLSEXPORT-03
**Success Criteria** (what must be TRUE):
  1. Usuario puede exportar cada uno de los 6 reportes contables existentes (Libro Mayor, libro auxiliar, movimientos por tercero, balance de comprobación, cartera de clientes, cuentas por pagar) en `.xlsx`, además del CSV ya existente
  2. Las columnas de moneda y fecha en el export de Excel son celdas numéricas/fecha nativas (ordenables/sumables), no texto
  3. El export de Excel reusa la misma fuente de datos que el export CSV de cada reporte, sin lógica de consulta duplicada
**Plans**: 4/4 plans complete
Plans:
- [x] 05-01-PLAN.md — Servicio compartido ExcelReportExporter (D-03/D-05, celdas tipadas nativas)
- [x] 05-02-PLAN.md — Refactor AccountingReportController: row-builders privados compartidos por reporte (D-04) + fix cast float
- [x] 05-03-PLAN.md — 6 rutas/métodos .xlsx (ExcelReportExporter + row-builders) con gate de autenticación
- [x] 05-04-PLAN.md — Botón "Exportar Excel" en las 6 páginas Filament de reporte (D-09)
**UI hint**: yes

### Phase 6: Limpieza de código huérfano ReteICA
**Goal**: El código muerto sin ruta que quedó de la implementación pre-Filament de reglas de retención ICA queda eliminado, sin dejar una trampa latente para quien intente re-conectarlo.
**Depends on**: Nothing técnicamente (cierre de gap de auditoría de la milestone v1.0)
**Gap Closure**: Cierra gap de integración `RETICA-02-legacy-route` del audit v1.0 (`.planning/v1.0-MILESTONE-AUDIT.md`)
**Requirements**: TECHDEBT-01
**Success Criteria** (what must be TRUE):
  1. `WithholdingRuleController`, `StoreWithholdingRuleRequest` y `resources/views/withholding-rules/*.blade.php` ya no existen en el repositorio
  2. `php artisan route:list` confirma que ninguna ruta activa referenciaba esos archivos antes de eliminarlos
  3. La suite de tests completa (`php artisan test --compact`) sigue pasando sin regresiones
**Plans**: 1/1 plans complete
Plans:
- [x] 06-01-PLAN.md — Auditoría grep + eliminación de WithholdingRuleController/StoreWithholdingRuleRequest/vistas blade huérfanas
**UI hint**: no

### Phase 7: Cuentas por pagar — alcance mercado privado
**Goal**: El reporte y export "cuentas por pagar" refleja también los `ExpenseRecord` del flujo de mercado privado (con `budget_obligation_id` nulo), no solo obligaciones presupuestales públicas — incluyendo los ICA-retenidos (Fase 2) y conciliados (Fase 3).
**Depends on**: Nothing técnicamente (cierre de gap de auditoría de la milestone v1.0)
**Gap Closure**: Cierra gap de flujo `accounts-payable-private-market-scope` del audit v1.0 (`.planning/v1.0-MILESTONE-AUDIT.md`); afecta XLSEXPORT-01, XLSEXPORT-03
**Requirements**: AP-01
**Success Criteria** (what must be TRUE):
  1. `AccountsPayable::openItems()` (o un origen de datos equivalente) incluye `ExpenseRecord`s sin `BudgetObligation` asociada, no solo `BudgetObligation`
  2. El reporte "cuentas por pagar" en pantalla, su CSV y su export Excel muestran los mismos `ExpenseRecord`s de mercado privado sin lógica de consulta duplicada entre los tres
  3. Un `ExpenseRecord` con retención ICA (Fase 2) o ya conciliado (Fase 3) aparece correctamente en el reporte mientras esté pendiente de pago
**Plans**: 0/1 plans complete
Plans:
- [ ] 07-01-PLAN.md — Combinar ExpenseRecords de mercado privado en AccountsPayable::openItems() (D-01/D-02/D-03) + cobertura Pest
**UI hint**: no

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7 (A → C → B → E → D → gap closure)

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Cotización electrónica (Fase A) | 4/4 | Complete   | 2026-09-16 |
| 2. ReteICA por municipio (Fase C) | 3/3 | Complete   | 2026-09-17 |
| 3. Conciliación bancaria CSV (Fase B) | 4/4 | Complete   | 2026-09-17 |
| 4. Hook de facturación externa (Fase E) | 1/1 | Complete   | 2026-09-18 |
| 5. Exportación Excel (Fase D) | 4/4 | Complete   | 2026-09-18 |
| 6. Limpieza de código huérfano ReteICA | 1/1 | Complete   | 2026-09-18 |
| 7. Cuentas por pagar — alcance mercado privado | 0/? | Not started | - |

# Requirements: ContPass — Mejoras Comerciales para Mercado Privado

**Defined:** 2026-09-16
**Core Value:** Cada movimiento relevante produce un comprobante contable auditable e inmutable por partida doble — trazabilidad e inmutabilidad sobre conveniencia.

## v1 Requirements

### Cotizaciones (QUOT) — Fase A

- [x] **QUOT-01**: Usuario puede crear una cotización para un tercero con una o más líneas (descripción, cantidad, valor unitario)
- [x] **QUOT-02**: La numeración de cotizaciones es consecutiva por empresa y segura bajo creación concurrente (sin duplicados entre solicitudes simultáneas)
- [x] **QUOT-03**: Usuario puede transicionar una cotización por los estados Borrador → Enviada → Aceptada/Rechazada; el estado Vencida se calcula automáticamente por fecha de validez, no es una transición manual
- [x] **QUOT-04**: Usuario puede generar un PDF de la cotización con datos de la empresa, líneas, subtotal/total y fecha de validez
- [x] **QUOT-05**: Usuario puede convertir una cotización Aceptada en un `IncomeRecord` con una sola acción, reusando el servicio `PostIncomeVoucher` existente
- [x] **QUOT-06**: Convertir la misma cotización dos veces nunca crea un segundo comprobante de ingreso (conversión idempotente, garantizada en la misma transacción que el cambio de estado)
- [x] **QUOT-07**: Una cotización convertida queda de solo lectura y muestra un enlace a su comprobante de ingreso resultante

### ReteICA por Municipio (RETICA) — Fase C

- [x] **RETICA-01**: Existe un catálogo de municipios colombianos (códigos DANE de departamento/municipio + nombres), construido desde cero para esta fase
- [x] **RETICA-02**: Usuario puede configurar una regla de retención ICA con vigencia, tarifa y base mínima propias, ligada a un municipio específico (reusando el versionado ya existente de `WithholdingRule`)
- [x] **RETICA-03**: El municipio usado para seleccionar la regla de ICA aplicable toma por defecto el domicilio registrado de la `Company`, con opción de edición manual
- [x] **RETICA-04**: Cuando existen reglas de ICA activas para distintos municipios, solo se aplica la regla que coincide con el municipio de la operación — nunca se acumulan retenciones ICA de más de un municipio en una misma transacción
- [x] **RETICA-05**: Las retenciones nacionales existentes (ReteFuente, ReteIVA) siguen aplicándose sin verse afectadas por la nueva dimensión de municipio

### Conciliación Bancaria por Extracto (BANKREC) — Fase B

- [x] **BANKREC-01**: Usuario puede subir un archivo de extracto bancario en CSV asociado a una `CashAccount` específica
- [x] **BANKREC-02**: El importador normaliza codificación de caracteres (Windows-1252/UTF-8) y reconoce formatos de fecha comunes en extractos colombianos antes de parsear
- [x] **BANKREC-03**: Reimportar un periodo de extracto ya importado se detecta y se rechaza/marca explícitamente, no se duplica en silencio
- [x] **BANKREC-04**: El sistema propone cruces entre líneas del extracto importado y `Payment` existentes usando monto, ventana de fecha y referencia, incluyendo el caso donde una línea del extracto corresponde a la suma de varios `Payment` (transferencias por lote)
- [x] **BANKREC-05**: Usuario debe confirmar explícitamente cada cruce propuesto antes de que se marque un `Payment` como conciliado — ningún cruce se confirma automáticamente
- [x] **BANKREC-06**: Las filas que no se puedan parsear (codificación inválida, fecha no reconocible) se muestran al usuario como filas rechazadas explícitas, no se omiten en silencio

### Hook de Facturación Externa (INVHOOK) — Fase E

- [x] **INVHOOK-01**: Usuario puede registrar manualmente una referencia de factura electrónica externa (número, CUFE, proveedor, URL del documento) asociada a un `IncomeRecord`, después de creado
- [x] **INVHOOK-02**: La referencia de factura externa se guarda en un registro relacionado propio, sin modificar el `IncomeRecord` inmutable
- [x] **INVHOOK-03**: Ninguna parte del sistema intenta llamar a una API externa de facturación usando estos campos (solo captura, sin integración activa)

### Exportación Excel (XLSEXPORT) — Fase D

- [ ] **XLSEXPORT-01**: Usuario puede exportar cada uno de los 6 reportes contables existentes (Libro Mayor, Libro auxiliar, movimientos por tercero, balance de comprobación, cartera de clientes, cuentas por pagar) en `.xlsx`, además del CSV ya existente
- [~] **XLSEXPORT-02**: Las columnas de moneda y fecha en el export de Excel son celdas numéricas/fecha nativas (ordenables/sumables), no texto
- [~] **XLSEXPORT-03**: El export de Excel reusa la misma fuente de datos que el export CSV de cada reporte (sin lógica de consulta duplicada)

## v2 Requirements

Reconocidas pero fuera de este milestone.

### Cotizaciones
- **QUOT-08**: Insignia/aviso visual de cotizaciones próximas a vencer
- **QUOT-09**: Catálogo reusable de productos/servicios frecuentes para líneas de cotización

### Conciliación Bancaria
- **BANKREC-07**: Confirmación masiva ("bulk-confirm") de un lote de cruces claramente correctos

### ReteICA
- **RETICA-06**: ReteICA por actividad económica (CIIU) del `ThirdParty`, en vez de domicilio de `Company`
- **RETICA-07**: Reportes/analítica de retención ICA por municipio

### Exportación Excel
- **XLSEXPORT-04**: Export multi-hoja para reportes con separación resumen+detalle (ej. Libro Mayor)

### Facturación Externa
- **INVHOOK-04**: Integración activa por API/webhook con un proveedor específico de facturación electrónica

## Out of Scope

Excluidos explícitamente de este milestone. Ver `docs/roadmap-apolo.md` y `.planning/PROJECT.md` para el detalle.

| Feature | Reason |
|---------|--------|
| Facturación electrónica propia (DIAN/CUFE) | Certificación como Proveedor Tecnológico Autorizado es un esfuerzo regulatorio grande; se integrará un proveedor tercero en una fase futura |
| Motor de cálculo de nómina electrónica | Mercado privado ya bien servido por competidores (Alegra/Siigo/Helisa); ContPass compite en trazabilidad/auditoría, no en nómina |
| Punto de venta (POS) | No encaja con la identidad de control/trazabilidad del producto |
| ReteICA por actividad económica (CIIU) del ThirdParty | Estándar real de mercado (Siigo/Siesa), pero descartado deliberadamente por simplicidad tras confirmarlo con el usuario durante el research de esta milestone |
| Conexión bancaria en vivo / Open Banking, soporte OFX | Fuera del alcance de Fase B; solo CSV con cruce manual/semiautomático |
| Conciliación bancaria asistida por IA | Emparejamiento no determinístico entra en conflicto directo con el Core Value de trazabilidad/auditabilidad |
| Generación/presentación de declaración de ICA ante la Secretaría de Hacienda municipal | Fase C solo calcula y registra la retención, no declara |
| CRM completo alrededor de cotizaciones (pipeline, seguimientos, automatización) | Diluye el foco de ContPass y compite de frente con herramientas de CRM dedicadas sin ventaja diferencial |
| Multi-moneda en cotizaciones/facturación | Sin evidencia de que el segmento objetivo (pymes colombianas facturando en COP) lo necesite |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| QUOT-01 | Phase 1 (Fase A) | Complete |
| QUOT-02 | Phase 1 (Fase A) | Complete |
| QUOT-03 | Phase 1 (Fase A) | Complete |
| QUOT-04 | Phase 1 (Fase A) | Complete |
| QUOT-05 | Phase 1 (Fase A) | Complete |
| QUOT-06 | Phase 1 (Fase A) | Complete |
| QUOT-07 | Phase 1 (Fase A) | Complete |
| RETICA-01 | Phase 2 (Fase C) | Complete |
| RETICA-02 | Phase 2 (Fase C) | Complete |
| RETICA-03 | Phase 2 (Fase C) | Complete |
| RETICA-04 | Phase 2 (Fase C) | Complete |
| RETICA-05 | Phase 2 (Fase C) | Complete |
| BANKREC-01 | Phase 3 (Fase B) | Complete |
| BANKREC-02 | Phase 3 (Fase B) | Complete |
| BANKREC-03 | Phase 3 (Fase B) | Complete |
| BANKREC-04 | Phase 3 (Fase B) | Complete |
| BANKREC-05 | Phase 3 (Fase B) | Complete |
| BANKREC-06 | Phase 3 (Fase B) | Complete |
| INVHOOK-01 | Phase 4 (Fase E) | Complete |
| INVHOOK-02 | Phase 4 (Fase E) | Complete |
| INVHOOK-03 | Phase 4 (Fase E) | Complete |
| XLSEXPORT-01 | Phase 5 (Fase D) | Pending |
| XLSEXPORT-02 | Phase 5 (Fase D) | Partial |
| XLSEXPORT-03 | Phase 5 (Fase D) | Partial |

**Coverage:**
- v1 requirements: 24 total
- Mapped to phases: 24
- Unmapped: 0 ✓

---
*Requirements defined: 2026-09-16*
*Last updated: 2026-09-18 after Phase 5 Plans 1-2 (ExcelReportExporter service + row-builder refactor) — XLSEXPORT-02/03 en progreso, cierran en Plan 3*

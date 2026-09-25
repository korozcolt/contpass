# ContPass: Base de Conocimiento Maestra del Sistema

> **Propósito:** Este documento contiene la arquitectura, alcance técnico, marco normativo, modelo de datos y flujos operativos completos de **ContPass**. Sirve como fuente de verdad para el equipo de desarrollo y para ser indexado en herramientas de IA y NotebookLM.
> **Última Actualización:** Septiembre 2026.
> **Entorno UAT:** https://contpass.kor-bytes.com/admin

---

## 1. Visión General del Software

ContPass es un Sistema Integrado de Gestión Financiera, Contable y Presupuestal diseñado específicamente bajo la normativa colombiana para **Empresas de Servicios Públicos (E.S.P.)**, entidades públicas descentralizadas y empresas privadas con control presupuestal estricto.

### Principios Rectores:
1. **Doble Partida Inmutable:** Todo movimiento económico genera asientos débitos y créditos estrictamente balanceados. Una vez aprobado un comprobante, no se puede alterar ni eliminar arbitrariamente; cualquier corrección se realiza mediante Notas de Ajuste auditadas.
2. **Cadena de Gasto Público Estricta:** Cumplimiento del Estatuto Orgánico del Presupuesto (Decreto 111 de 1996): ningún gasto se ejecuta sin disponibilidad presupuestal previa (**Apropiación ➔ CDP ➔ RP ➔ Obligación ➔ Orden de Pago ➔ Giro/Egreso**).
3. **Numeración Automática Correlativa:** Eliminación del error humano y de la manipulación de secuencias. Los comprobantes contables, certificados presupuestales y movimientos de almacén reciben numeración consecutiva institucional por vigencia fiscal y tipo de documento (`ING-`, `EGR-`, `PAG-`, `AJU-`, `PRE-`, `CDP-`, `RP-`, `OBL-`, `OP-`, `ENT-`, `SAL-`, `TRA-`).
4. **Trazabilidad y Auditoría Permanente:** Trait `Auditable` en todos los modelos para registrar qué usuario creó, aprobó o modificó cada registro con marca de tiempo.

---

## 2. Stack Tecnológico

- **Lenguaje:** PHP 8.4.23 (Tipado estricto, constructor promotion, enumeraciones nativas).
- **Framework Web:** Laravel 13.18.1.
- **Panel Administrativo:** Filament v5.0 (Taller de componentes Livewire 4 y Tailwind CSS v4).
- **Bases de Datos:** PostgreSQL en producción y entornos UAT; SQLite para testing rápido.
- **Testing:** Pest v4 (272 pruebas automatizadas y 901 aserciones con 100% de cobertura funcional).
- **Estándar de Código:** Laravel Pint (Reglas estrictas de la comunidad Laravel).
- **Despliegue e Infraestructura:** Dokploy sobre Docker Swarm en servidor Linux Ubuntu (`korserver`), Nginx 1.26 con HTTP/2 y certificados SSL automáticos.

---

## 3. Estructura de Módulos del Sistema

ContPass organiza sus recursos en 8 grandes áreas operativas:

### 3.1. Configuración Institucional
- **Empresas (`companies`):** Multi-tenant básico. Define razón social, NIT con dígito de verificación DIAN, naturaleza jurídica (Pública, Privada, Mixta, E.S.P.), códigos DANE departamental y municipal, y representante legal.
- **Periodos Contables (`accounting_periods`):** Delimitan los meses o vigencias fiscales. Controlan si un mes está "Abierto" o "Cerrado" para impedir causaciones extemporáneas.
- **Firmantes Oficiales (`company_signatories`):** Registro de cargos autorizados (Gerente, Director Financiero, Contador, Revisor Fiscal, Almacenista) para encabezados y pies de página de reportes oficiales.
- **Dependencias (`dependencies`):** Centros de costo y áreas funcionales (Gerencia General, Dirección Técnica, Comercial, Financiera, Almacén General).
- **Usuarios y Roles (`users`):** Control de acceso institucional con credenciales encriptadas.

### 3.2. Contabilidad General (PUC y Libros Oficiales)
- **Plan Único de Cuentas (`chart_accounts`):** Estructura jerárquica con código, nombre y naturaleza (Débito/Crédito). Soporta PUC para E.S.P. y comercial.
- **Comprobantes Contables (`vouchers`):** Encabezados de comprobantes con fecha, descripción, tercero asociado, estado (`draft`, `approved`, `cancelled`, `adjusted`) y numeración automática correlativa.
- **Asientos Contables (`accounting_entries`):** Líneas individuales del comprobante con cuenta PUC, débito y crédito.
- **Informes Contables:**
  - Balance de Prueba (Comprobación).
  - Estado de Situación Financiera (Balance General).
  - Estado de Resultados (Pérdidas y Ganancias).
  - Libro Mayor y Balances.
  - Libro Diario Columnario.
  - Auxiliares Contables por Cuenta y Tercero.
  - Exportador nativo a hojas de cálculo Excel (`.xlsx`).

### 3.3. Presupuesto de Ingresos
- **Rubros de Ingresos (`budget_revenues`):** Catálogo de fuentes de financiación (venta de agua potable, alcantarillado, transferencias SGP, subsidios, rendimientos financieros) con su valor presupuestado inicial, adiciones, reducciones y recaudos acumulados.
- **Causación y Recaudo de Ingresos (`income_records`):** Registro de derechos de cobro y recaudos en bancos vinculados al rubro presupuestal y a la cuenta contable PUC de la clase 4.

### 3.4. Presupuesto de Gastos (Cadena de Ejecución Presupuestal)
- **Apropiaciones Presupuestales (`budget_appropriations`):** Techo presupuestal por rubro de gasto para la vigencia fiscal (ej: Sueldos de personal, reactivos químicos, mantenimiento de redes, energía eléctrica).
- **Modificaciones Presupuestales (`budget_modifications`):** Adiciones, reducciones y traslados presupuestales soportados por actos administrativos formales (Decretos o Resoluciones).
- **Certificados de Disponibilidad Presupuestal - CDP (`budget_availability_certificates`):** Garantiza la existencia de saldo libre antes de contratar.
- **Registros Presupuestales - RP (`budget_registrations`):** Compromete el recurso a favor de un tercero contratista o proveedor específico.
- **Obligaciones Presupuestales (`budget_obligations`):** Reconocimiento de la deuda contra entrega de bien/servicio y radicación de factura.
- **Órdenes de Pago (`payment_orders`):** Instrucción formal de tesorería para girar los recursos.
- **Programación Anual de Caja - P.A.C. (`cash_program_items`):** Calendario mensual de metas de pago para evitar déficit de tesorería.

### 3.5. Tesorería y Bancos
- **Cuentas de Caja y Bancos (`cash_accounts`):** Vinculación de cuentas corrientes, de ahorros y cajas generales a cuentas contables clase 11.
- **Pagos y Egresos (`payments`):** Generación de pagos con especificación de método (transferencia, cheque, efectivo), control de bancarización (Art. 771-5 E.T.) y causación automática del comprobante de egreso.
- **Cajas Menores (`petty_cash_funds` y `petty_cash_movements`):** Manejo de fondos fijos, recibos de desembolso menor, arqueos y legalización de reembolsos.
- **Conciliación Bancaria (`bank_statements`):** Importación de extractos bancarios e identificación de partidas conciliatorias.

### 3.6. Almacén e Inventarios
- **Bodegas / Sedes (`warehouses`):** Espacios físicos de almacenamiento.
- **Catálogo de Artículos (`warehouse_items`):** Insumos consumibles (químicos, papelería) y devolutivos (herramientas, medidores) con control de stock mínimo y código automático `ART-XXXXX`.
- **Movimientos de Inventario (`warehouse_movements` y `warehouse_movement_lines`):** Entradas por compra, salidas para consumo de dependencias operativas y traslados entre bodegas con método promedio ponderado.

### 3.7. Recursos Humanos y Nómina
- **Fondos de Seguridad Social (`payroll_funds`):** EPS, Fondos de Pensiones, Cesantías y Cajas de Compensación Familiar con su NIT y código DIAN.
- **Trabajadores (`employees`):** Ficha del empleado con cédula, cargo, salario base, dependencia y afiliaciones.
- **Conceptos de Nómina (`payroll_concepts`):** Devengados (sueldo, auxilio de transporte, horas extras) y deducciones (salud, pensión, retenciones).

### 3.8. Terceros y Reglas Tributarias
- **Terceros (`third_parties`):** Base unificada de clientes, contratistas, empleados y entidades estatales con validación del Dígito de Verificación (algoritmo Módulo 11 de la DIAN).
- **Reglas de Retención (`withholding_rules`):** Configuración paramétrica de Retención en la Fuente, ReteIVA y ReteICA (con catálogo DANE) por vigencia y bases mínimas en UVT.

---

## 4. Cliente Piloto de Referencia: Aguas de Sucre S.A. E.S.P.

- **Razón Social:** Aguas de Sucre S.A. E.S.P.
- **NIT:** 900.247.655-1
- **Naturaleza:** Empresa de Servicios Públicos Mixta.
- **Ubicación:** Cra 18 # 20-45, Edificio San José, Piso 3, Sincelejo (Sucre). Códigos DANE: Depto 70, Mpio 001.
- **Periodo Activo:** 2026.
- **Presupuesto Aprobado:**
  - Ingresos: $3.295.000.000 COP (Venta de Agua, Alcantarillado, SGP).
  - Gastos: $2.970.000.000 COP ($2.770M iniciales + $200M adición sequía).
- **Usuarios de Demostración:**
  - Administrador General: `admin@aguasdesucre.com.co` / `AguasDeSucre2026*`
  - Contador: `contador@aguasdesucre.com.co` / `AguasDeSucre2026*`

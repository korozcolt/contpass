# ContPass

ContPass es una aplicación interna de control contable para empresas en Colombia. Nace de una necesidad práctica: registrar ingresos, egresos, pagos, terceros, retenciones, bancarización y soportes con trazabilidad suficiente para que el contador pueda revisar, conciliar y exportar información confiable.

El sistema no reemplaza la facturación electrónica ni automatiza el cierre fiscal completo. Su objetivo es sostener el registro operativo de causación, caja/bancos y auxiliares contables bajo una estructura clara, auditable y preparada para reglas colombianas vigentes a 2026.

## Alcance Funcional

- Gestión de terceros con NIT/Cédula y DV validado con algoritmo DIAN.
- Plan Único de Cuentas configurable por empresa.
- Cuentas de caja y bancos asociadas a cuentas PUC clase 11.
- Reglas de retención versionadas por vigencia, base mínima, tarifa, cuenta contable y concepto.
- Causación de ingresos con cuenta por cobrar e ingreso.
- Causación de egresos con gasto/costo, cuenta por pagar, soporte idóneo, deducibilidad y retenciones.
- Registro de pagos con medio de pago y control de bancarización.
- Comprobantes contables con encabezado y detalle bajo partida doble.
- Periodos contables abiertos o cerrados.
- Notas de ajuste para corregir comprobantes aprobados sin romper trazabilidad.
- Reportes CSV: libro auxiliar, movimientos por tercero y balance de comprobación.

## Principios Del Sistema

ContPass está diseñado alrededor de causación, trazabilidad e inmutabilidad.

Cada movimiento relevante se transforma en un comprobante contable. El comprobante contiene una fecha, tercero, tipo, estado, descripción y líneas contables. La suma de débitos y créditos debe balancear siempre.

Una vez aprobado un comprobante, no se corrige editándolo directamente. Cualquier corrección debe realizarse mediante una nota de ajuste que deje evidencia del cambio y conserve el historial del registro original.

Las reglas tributarias, como retenciones, no se guardan como constantes permanentes en código. Se administran como configuración versionada por fecha para responder a cambios normativos.

## Referente Normativo

El diseño toma como base la Ley 1314 de 2009, el Decreto 2420 de 2015 y sus actualizaciones vigentes a 2026. También contempla el control de medios de pago del Art. 771-5 del Estatuto Tributario para alertar sobre pagos en efectivo o no bancarizados que puedan afectar deducibilidad.

ContPass no emite facturación electrónica ni reporta directamente a la DIAN. Su papel es entregar información ordenada y exportable para revisión contable y conciliación.

## Flujos Operativos

### Terceros

1. Se registra el tipo de tercero: persona natural o persona jurídica.
2. Se captura nombre, NIT/Cédula y DV separado.
3. El sistema valida el DV para reducir errores de digitación.
4. El tercero queda disponible para ingresos, egresos, pagos y reportes.

### Ingresos

1. Se selecciona el tercero.
2. Se indica la fecha de causación.
3. Se selecciona la cuenta de ingreso clase 4.
4. Se selecciona la cuenta por cobrar clase 13.
5. Se registra soporte, descripción y valor.
6. El sistema genera un comprobante aprobado con débito a cuenta por cobrar y crédito a ingreso.

### Egresos

1. Se selecciona el tercero proveedor.
2. Se indica la fecha de causación.
3. Se selecciona la cuenta de gasto clase 5 o costo clase 6.
4. Se selecciona la cuenta por pagar clase 2.
5. Se registra soporte, valor, deducibilidad y si el soporte es idóneo.
6. El sistema evalúa reglas de retención vigentes.
7. Se genera un comprobante aprobado con gasto/costo, retención y cuenta por pagar.

### Pagos

1. Se selecciona el comprobante origen cuando aplica.
2. Se selecciona caja o banco.
3. Se define la contrapartida contable.
4. Se selecciona el medio de pago: transferencia, cheque, tarjeta, depósito, efectivo u otro autorizado.
5. Se registra fecha, referencia, descripción y valor.
6. El sistema marca pagos en efectivo como no bancarizados para revisión tributaria.

### Ajustes

1. Se identifica el comprobante aprobado que requiere corrección.
2. Se crea una nota de ajuste con sus propias líneas débito/crédito.
3. La nota debe balancear por partida doble.
4. El comprobante original queda marcado como ajustado.
5. El historial conserva la relación entre comprobante original y ajuste.

### Periodos Contables

1. Se crea un periodo con fecha inicial y final.
2. Mientras esté abierto, el sistema permite causaciones y pagos dentro del rango.
3. Al cerrar el periodo, se bloquean causaciones directas sobre esas fechas.
4. Las correcciones posteriores deben pasar por ajustes controlados.

## Módulos Principales

- `Catálogos`: terceros, plan de cuentas, caja/bancos y reglas de retención.
- `Operación`: ingresos, egresos y pagos.
- `Control`: comprobantes y periodos contables.
- `Reportes`: libro auxiliar, movimientos por tercero y balance de comprobación.
- `Seguridad`: usuarios y roles internos.

## Estados Contables

- `borrador`: registro en preparación.
- `aprobado`: comprobante válido que impacta auxiliares.
- `anulado`: comprobante invalidado sin eliminar historial.
- `ajustado`: comprobante aprobado que tiene una nota de ajuste asociada.

## Roles

- `admin`: administración completa del sistema.
- `accountant`: operación contable y revisión de información.
- `viewer`: rol sin acceso al panel administrativo.

## Convenciones De Interfaz

La operación principal vive en Filament bajo el panel administrativo. Las vistas Blade operativas fueron deprecadas para concentrar la experiencia en una sola interfaz.

Los formularios usan formatos visibles según el dato:

- Valores monetarios con prefijo `COP $` e icono de moneda.
- Porcentajes con sufijo `%`.
- Fechas mediante selector visual.
- NIT/Cédula y DV separados.
- Cuentas PUC y terceros mediante selects buscables.
- Booleanos mediante toggles visibles.
- Medios de pago mediante select con advertencia para efectivo.

## Reportes

El libro auxiliar permite revisar movimientos por cuenta, tercero y fecha. Es la base para inspeccionar el detalle de débitos y créditos.

El reporte de movimientos por tercero agrupa la actividad asociada a clientes, proveedores u otros terceros, útil para cruces contables y revisión documental.

El balance de comprobación resume débitos, créditos y saldos por cuenta en un rango de fechas.

Todos los reportes se exportan en CSV UTF-8 para facilitar revisión en hojas de cálculo y cruces externos.

## Decisiones Importantes

- La lógica contable vive en servicios de dominio, no en los formularios.
- Los formularios operativos no crean asientos directamente.
- Las cuentas PUC se validan por clase según el tipo de operación.
- Los comprobantes aprobados son inmutables.
- Las retenciones son configurables por vigencia.
- PostgreSQL es la base de datos objetivo del sistema.
- Redis queda previsto para cache, sesiones y colas.
- El idioma operativo por defecto es español colombiano.

## Estado Del MVP

ContPass cubre el ciclo base de control interno: catálogos, causación, retención, pagos, bancarización, comprobantes, ajustes, periodos y reportes auxiliares.

Pendientes naturales para fases posteriores:

- Multiempresa real por usuario.
- Políticas granulares por recurso.
- Exportación Excel dedicada.
- Conciliación bancaria asistida.
- Importación masiva de extractos o soportes.
- Parametrización tributaria ampliada por ciudad e ICA.

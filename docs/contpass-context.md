# ContPass: Contexto del Sistema

ContPass es una aplicación interna de control contable para empresas en Colombia. Nace de la necesidad de registrar ingresos, egresos, pagos, retenciones y bancarización con trazabilidad suficiente para que un contador pueda preparar conciliaciones, revisar soportes y exportar información sin fricción.

El sistema no reemplaza la facturación electrónica ni pretende liquidar todo el cierre fiscal. Su foco es la causación, la partida doble, el control técnico de caja/bancos y la generación de auxiliares confiables.

## Alcance Actual

- Registro de terceros con NIT/Cédula y DV validado con algoritmo DIAN.
- Plan Único de Cuentas configurable por empresa.
- Cuentas de caja y bancos asociadas a cuentas PUC clase 11.
- Reglas de retención configurables por vigencia, base mínima, tarifa y cuenta contable.
- Causación de ingresos con cuenta por cobrar e ingreso.
- Causación de egresos con gasto/costo, cuenta por pagar y retenciones automáticas.
- Registro de pagos con medio de pago y bandera de bancarización.
- Comprobantes aprobados e inmutables.
- Notas de ajuste para corregir comprobantes aprobados.
- Periodos contables con cierre operativo.
- Reportes CSV: libro auxiliar, movimientos por tercero y balance de comprobación.
- Panel administrativo Filament v5 en `/admin`.

## Referente Normativo

El diseño funcional se apoya en la Ley 1314 de 2009, el Decreto 2420 de 2015 y sus actualizaciones vigentes a 2026, además del control de bancarización del Art. 771-5 del Estatuto Tributario. Las reglas de retención no están quemadas en código: se configuran por vigencia para responder a cambios normativos.

## Arquitectura

ContPass usa Laravel 13, PHP 8.4, SQLite en desarrollo y Filament 5.6 como interfaz administrativa. La capa Filament captura la intención del usuario, pero la lógica contable vive en servicios de dominio:

- `ValidateColombianTaxId`
- `PostIncomeVoucher`
- `PostExpenseVoucher`
- `ApplyWithholdingRules`
- `RegisterPayment`
- `CreateAdjustmentVoucher`
- `PostsBalancedVoucher`
- `EnsureOpenAccountingPeriod`

Esta separación es intencional: los formularios no deben crear asientos directamente. Toda operación que impacte contabilidad pasa por servicios que validan periodo abierto, crean comprobante, generan líneas débito/crédito y preservan la trazabilidad.

## Modelo De Datos

Entidades principales:

- `Company`: empresa actual del MVP.
- `ThirdParty`: cliente/proveedor/tercero con identificación tributaria.
- `ChartAccount`: cuenta PUC con código, nombre y naturaleza.
- `CashAccount`: caja o banco asociado a una cuenta PUC.
- `WithholdingRule`: regla de retención por concepto, base, tarifa y vigencia.
- `AccountingPeriod`: rango de fechas abierto o cerrado.
- `Voucher`: encabezado del comprobante.
- `AccountingEntry`: líneas débito/crédito del comprobante.
- `IncomeRecord`: detalle operativo del ingreso causado.
- `ExpenseRecord`: detalle operativo del egreso causado.
- `Payment`: registro de pago o recaudo.

La suma de débitos y créditos de cada comprobante debe ser igual. Los comprobantes aprobados no se editan directamente.

## Flujos Operativos

### Ingresos

1. Seleccionar tercero.
2. Indicar fecha de causación.
3. Seleccionar cuenta de ingreso clase 4.
4. Seleccionar cuenta por cobrar clase 13.
5. Registrar soporte y valor.
6. El sistema crea comprobante aprobado con débito a cuenta por cobrar y crédito a ingreso.

### Egresos

1. Seleccionar tercero.
2. Indicar fecha de causación.
3. Seleccionar cuenta gasto/costo clase 5 o 6.
4. Seleccionar cuenta por pagar clase 2.
5. Registrar soporte, valor, deducibilidad y soporte idóneo.
6. El sistema calcula retenciones vigentes y crea comprobante aprobado con gasto/costo, retención y cuenta por pagar.

### Pagos

1. Seleccionar comprobante origen si aplica.
2. Seleccionar caja/banco.
3. Seleccionar contrapartida contable.
4. Seleccionar medio de pago.
5. Registrar fecha, referencia y valor.
6. El sistema marca pagos en efectivo como no bancarizados para revisión tributaria.

### Ajustes

Cuando un comprobante aprobado necesita corrección, se crea una nota de ajuste con líneas contables propias. El comprobante original queda marcado como ajustado y la corrección conserva trazabilidad.

## Convenciones De UI En Filament

- Valores monetarios usan prefijo visible `COP $` e icono de moneda.
- Porcentajes usan sufijo `%`.
- Fechas usan DatePicker.
- NIT/Cédula y DV están separados.
- Cuentas PUC se muestran como `código · nombre`.
- Terceros se muestran como `identificación · nombre`.
- Booleanos usan toggles.
- Medios de pago usan select; efectivo genera alerta de no bancarización.

## Acceso

Filament está disponible en `/admin`. Los roles mínimos son:

- `admin`: acceso completo.
- `accountant`: acceso operativo contable.
- `viewer`: sin acceso al panel administrativo.

Usuario seed inicial:

- Correo: `admin@example.com`
- Contraseña: `password`

## Desarrollo Y Verificación

Comandos principales:

```bash
composer install
php artisan migrate:fresh --seed --no-interaction
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

En Laravel Herd, la URL esperada es:

```text
http://contpass.test/admin
```

## Estado Y Pendientes Naturales

El MVP queda orientado a operación interna. Pendientes posibles para fases futuras:

- Multiempresa real por usuario.
- Políticas granulares por recurso.
- Exportación Excel dedicada además de CSV.
- Conciliación bancaria asistida.
- Importación masiva de extractos o soportes.
- Parametrización tributaria más amplia por ciudad/ICA.

#set document(title: "Manual Maestro Integral ContPass (Para Dummies)", author: "ContPass Team")
#set page(
  paper: "a4",
  margin: (top: 2.2cm, bottom: 2.2cm, left: 2.2cm, right: 2.2cm),
  header: context {
    if counter(page).get().first() > 1 [
      #grid(
        columns: (1fr, auto),
        align: (left + horizon, right + horizon),
        text(size: 8pt, fill: rgb("#64748b"), weight: "bold", [CONTPASS · MANUAL INTEGRAL DE USO OFICIAL]),
        text(size: 8pt, fill: rgb("#94a3b8"), [Todos los Módulos · Paso a Paso])
      )
      #v(-4pt)
      #line(length: 100%, stroke: 0.5pt + rgb("#e2e8f0"))
    ]
  },
  footer: context {
    if counter(page).get().first() > 1 [
      #line(length: 100%, stroke: 0.5pt + rgb("#e2e8f0"))
      #v(2pt)
      #grid(
        columns: (1fr, auto),
        align: (left + horizon, right + horizon),
        text(size: 8pt, fill: rgb("#94a3b8"), [ContPass ERP · Aguas de Sucre S.A. E.S.P.]),
        text(size: 8.5pt, fill: rgb("#64748b"), weight: "bold", [Página #counter(page).display()])
      )
    ]
  }
)

#set text(font: "Helvetica", size: 9pt, fill: rgb("#1e293b"), lang: "es")
#set par(justify: true, leading: 0.6em)

#let callout(title, body, color: rgb("#0284c7"), bg: rgb("#f0f9ff")) = {
  rect(
    width: 100%,
    radius: 5pt,
    fill: bg,
    stroke: 0.8pt + color,
    inset: (x: 10pt, y: 8pt),
    [
      #text(weight: "bold", fill: color, size: 9.5pt, title) \
      #v(1pt)
      #text(size: 8.5pt, body)
    ]
  )
}

#let danger-callout(title, body) = callout(title, body, color: rgb("#dc2626"), bg: rgb("#fef2f2"))
#let warning-callout(title, body) = callout(title, body, color: rgb("#d97706"), bg: rgb("#fffbeb"))
#let success-callout(title, body) = callout(title, body, color: rgb("#16a34a"), bg: rgb("#f0fdf4"))

// ==========================================
// PORTADA
// ==========================================
#align(center)[
  #v(1.5cm)
  #image("/public/images/brand/contpass-logo-horizontal.png", width: 55%)
  
  #v(1.8cm)
  #text(size: 24pt, weight: "black", fill: rgb("#0f172a"), [MANUAL MAESTRO DE USO INTEGRAL]) \
  #v(4pt)
  #text(size: 14pt, weight: "bold", fill: rgb("#0284c7"), [GUÍA PASO A PASO PARA PRINCIPIANTES ABSOLUTOS (DUMMIES)]) \
  #v(6pt)
  #text(size: 10pt, fill: rgb("#64748b"), [Cubre absolutamente TODO el sistema: el Dashboard completo, cada widget, todos los 46 ítems del menú lateral, creación, edición, anulación y exportación a Excel.])

  #v(2.5cm)
  #rect(
    width: 90%,
    radius: 8pt,
    fill: rgb("#f8fafc"),
    stroke: 1pt + rgb("#cbd5e1"),
    inset: 12pt,
    [
      #text(weight: "bold", size: 10.5pt, fill: rgb("#0f172a"), [Entorno de Pruebas y Operación:]) \
      #v(2pt)
      #text(size: 9.5pt, fill: rgb("#334155"), [Entidad de Referencia: *Aguas de Sucre S.A. E.S.P.* (NIT: 900247655-1)]) \
      #text(size: 9.5pt, fill: rgb("#334155"), [Dirección Web: #link("https://contpass.kor-bytes.com/admin")[#strong("https://contpass.kor-bytes.com/admin")]]) \
      #text(size: 9pt, fill: rgb("#64748b"), [Vigencia: 2026 · Versión 3.0 Exhaustiva Total])
    ]
  )
]

#pagebreak()

// ==========================================
// ÍNDICE COMPLETO
// ==========================================
#heading(numbering: none)[Índice General de Módulos y Pantallas]
#v(4pt)

#columns(2, gutter: 15pt)[
  *0. Reglas de Oro y Login* \
  *1. El Dashboard (Pantalla de Inicio)* \
  - Las 4 Tarjetas de Estadísticas \
  - Tabla de Actividad Reciente \
  *2. Presupuesto de Ingresos* \
  - 2.1 Rubros de Ingresos \
  *3. Presupuesto de Gastos* \
  - 3.1 Apropiaciones Presupuestales \
  - 3.2 Modificaciones Presupuestales \
  - 3.3 Certificados de Disponibilidad (CDP) \
  - 3.4 Registros Presupuestales (RP) \
  - 3.5 Obligaciones Presupuestales \
  *4. Tesorería y Bancos* \
  - 4.1 Programación Anual de Caja (PAC) \
  - 4.2 Órdenes de Pago \
  - 4.3 Pagos / Egresos \
  - 4.4 Caja Menor (Fondos y Movimientos) \
  - 4.5 Importar Extracto Bancario \
  - 4.6 Conciliación Bancaria \
  *5. Operación Diaria* \
  - 5.1 Ingresos (Causación) \
  - 5.2 Egresos (Facturas de Proveedores) \
  - 5.3 Cotizaciones Comerciales \
  *6. Control y Comprobantes* \
  - 6.1 Periodos Contables \
  - 6.2 Comprobantes Contables \
  *7. Catálogos Maestros* \
  - 7.1 Terceros (Clientes y Proveedores) \
  - 7.2 Plan de Cuentas (PUC) \
  - 7.3 Caja y Bancos \
  - 7.4 Reglas de Retención \
  - 7.5 Almacenes / Bodegas \
  - 7.6 Elementos / Artículos \
  - 7.7 Dependencias (Áreas) \
  *8. Almacén e Inventarios* \
  - 8.1 Movimientos (Entradas, Salidas, Traslados) \
  - 8.2 Stock de Elementos \
  - 8.3 Auxiliar de Elementos (Kardex) \
  *9. Recursos Humanos y Nómina* \
  - 9.1 Fondos de Seguridad Social \
  - 9.2 Empleados \
  - 9.3 Conceptos de Nómina \
  *10. Cuentas por Cobrar y Pagar* \
  - 10.1 Cartera de Clientes \
  - 10.2 Cuentas por Pagar \
  *11. Reportes y Estados Financieros* \
  - 11.1 Balance de Prueba \
  - 11.2 Balance General \
  - 11.3 Estado de Resultados \
  - 11.4 Libro Mayor y Balances \
  - 11.5 Libro Diario \
  - 11.6 Libros Auxiliares \
  - 11.7 Ejecución Presupuestal \
  *12. Configuración y Auditoría* \
  - 12.1 Datos de la Empresa \
  - 12.2 Firmantes Autorizados \
  - 12.3 Usuarios del Sistema \
  - 12.4 Pista de Auditoría (Logs) \
  - 12.5 Rendición de Cuentas
]

#v(8pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// REGLAS BÁSICAS Y LOGIN
// ==========================================
#heading(level: 1)[0. Reglas de Oro y Acceso al Sistema]

#danger-callout("REGLA 1: Las casillas grises están BLOQUEADAS por diseño", [
  En comprobantes, CDPs, RPs, Obligaciones y Órdenes de Pago, el número viene en gris. *El sistema le asignará el consecutivo oficial automáticamente al guardar*. No intentes hacer clic ni reportes que está dañado.
])

#warning-callout("REGLA 2: Asterisco rojo (*) significa obligatorio", [
  Si dejas una casilla obligatoria vacía, la pantalla no avanzará y te mostrará un borde rojo indicando el error.
])

*Cómo entrar al sistema:*
1. Abre tu navegador web y entra a #link("https://contpass.kor-bytes.com/admin/login")[#strong("https://contpass.kor-bytes.com/admin/login")].
2. Escribe tu correo (Ej: `admin@aguasdesucre.com.co`) y tu contraseña (`AguasDeSucre2026*`).
3. Haz clic en el botón negro *Iniciar sesión*.

#pagebreak()

// ==========================================
// SECCIÓN 1: EL DASHBOARD
// ==========================================
#heading(level: 1)[1. El Dashboard (Pantalla de Inicio)]
#text(fill: rgb("#64748b"), style: "italic", [Es lo primero que ves al entrar. Es el panel de control general de la empresa.])

#v(4pt)
=== Las 4 Tarjetas de Estadísticas (AccountingStats)
En la parte superior verás 4 rectángulos grandes con números. Esto es lo que significa cada uno:

1. *Ingresos del mes:* Suma todo el dinero que ha entrado o se ha causado a favor de la empresa durante el mes actual. Abajo te muestra en porcentaje si hemos recibido más o menos plata que el mes pasado, junto con una gráfica de curva verde.
2. *Egresos del mes:* Suma todas las compras, gastos y facturas radicadas por proveedores en el mes actual. La gráfica roja muestra cómo se han comportado los gastos.
3. *Resultado operativo:* Es la resta automática: *Ingresos menos Egresos*.
   - Si sale en *Verde*: La empresa va ganando plata en el mes (los ingresos superan los gastos).
   - Si sale en *Rojo*: Alerta, estamos gastando más plata de la que está entrando.
4. *Pagos no bancarizados (Control DIAN Art. 771-5):* Cuenta cuántos pagos se hicieron en efectivo sin pasar por el banco. La ley tributaria colombiana sanciona pagar sumas grandes en efectivo. Si este número está en verde ("Sin alertas"), todo está perfecto; si está en rojo, hay pagos en efectivo que el contador debe revisar.

=== Tabla de Actividad Contable Reciente (RecentVouchers)
Debajo de las 4 tarjetas hay una tabla con los últimos comprobantes generados:
- *Comprobante:* Muestra el número oficial (`ING-`, `EGR-`, `AJU-`), la fecha, el tipo y el nombre del tercero.
- *Estado:* Si dice *Borrador* (aún editable) o *Aprobado* (inmutable, ya afecta contabilidad).
- *Valor:* El monto total en pesos COP que se movió.

#v(10pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// SECCIÓN 2: PRESUPUESTO DE INGRESOS
// ==========================================
#heading(level: 1)[2. Presupuesto de Ingresos]

=== 2.1 Rubros de Ingresos
- *¿Dónde está?* Menú lateral > *Presupuesto de Ingresos* > *Rubros de Ingresos*.
- *¿Para qué sirve?* Define las fuentes de dinero que la empresa tiene derecho a recaudar en el año (Venta de agua potable, alcantarillado, transferencias SGP, subsidios).
- *Acción Crear:* Haz clic en el botón negro *Crear Rubro de Ingresos*.
  - `Vigencia Fiscal *`: Año del presupuesto (Ej: `2026`).
  - `Código *`: Código oficial según el clasificador presupuestal (Ej: `1.2.05.01`).
  - `Nombre *`: Descripción clara (Ej: *Ingresos por Venta de Agua Potable*).
  - `Apropiación Inicial *`: Valor total proyectado para todo el año en pesos (Ej: `1850000000`).
  - `Cuenta Contable PUC`: Selecciona la cuenta de la clase 4 donde se causará contablemente este ingreso (Ej: `410505`).
- *Acción Editar:* Haz clic en el icono de lápiz en la tabla. Solo se pueden editar nombres o cuentas asociadas.
- *Acción Eliminar:* Solo se puede borrar si NO tiene recaudos asociados. Si ya tiene plata recaudada, el sistema bloqueará la eliminación para proteger los balances.

#pagebreak()

// ==========================================
// SECCIÓN 3: PRESUPUESTO DE GASTOS
// ==========================================
#heading(level: 1)[3. Presupuesto de Gastos (Cadena Completa)]
#text(fill: rgb("#64748b"), style: "italic", [Aquí se gestiona el dinero que la empresa puede gastar. Debe cumplirse en estricto orden:])

#v(4pt)
=== 3.1 Apropiaciones Presupuestales
- *¿Dónde está?* Menú lateral > *Presupuesto de Gastos* > *Apropiaciones*.
- *¿Para qué sirve?* Es la bolsa de dinero aprobada por la Asamblea o Junta para cada concepto de gasto.
- *Crear Apropiación:* Botón negro *Crear Apropiación*.
  - `Vigencia Fiscal *`: `2026`.
  - `Código *`: Código presupuestal (Ej: `2.1.2.02.01`).
  - `Nombre *`: Concepto (Ej: *Compra de Químicos y Reactivos*).
  - `Monto Inicial *`: Techo total de dinero para todo el año (Ej: `420000000`).
- *Consultar:* En la tabla puedes ver cuánto dinero queda disponible en cada rubro.

=== 3.2 Modificaciones Presupuestales
- *¿Dónde está?* Menú lateral > *Presupuesto de Gastos* > *Modificaciones Presupuestales*.
- *¿Para qué sirve?* Para meterle más plata a un rubro (Adición) o quitarle (Reducción) cuando el Gerente o la Junta emiten un Decreto o Resolución.
- *Crear:* Botón *Crear Modificación Presupuestal*.
  - `Tipo *`: Selecciona *Adición*, *Reducción* o *Traslado*.
  - `Rubro de gasto *`: A qué bolsa de gasto se le modifica el valor.
  - `Acto administrativo *`: Escribe el número del documento legal (Ej: *Decreto 014 de 2026*).
  - `Monto *`: Cuánta plata se añade o reduce.
  - `Concepto *`: Justificación (Ej: *Adición para emergencia de sequía*).

=== 3.3 Certificados de Disponibilidad Presupuestal (CDP)
- *¿Dónde está?* Menú lateral > *Presupuesto de Gastos* > *CDPs*.
- *¿Para qué sirve?* Certifica que sí hay saldo libre antes de abrir una compra o contrato.
- *Crear CDP:* Botón *Crear Certificado*.
  - 🛑 `Número de CDP`: *BLOQUEADO*. El sistema asigna `CDP-2026-000001` al guardar.
  - `Rubro presupuestal *`: Escoge el rubro de gasto. Te muestra el saldo disponible al lado.
  - `Fecha de emisión *`: Fecha de hoy.
  - `Monto *`: Plata que vas a congelar (Ej: `85000000`).
  - `Objeto del gasto *`: Para qué es la compra.
- *Anulación:* En la tabla, botón de acción *Anular*. Solo se puede anular si no tiene RPs expedidos.

=== 3.4 Registros Presupuestales (RP)
- *¿Dónde está?* Menú lateral > *Presupuesto de Gastos* > *RPs*.
- *¿Para qué sirve?* Amarra el dinero del CDP a nombre exclusivo del contratista ganador.
- *Crear RP:* Botón *Crear Registro*.
  - 🛑 `Número de RP`: *BLOQUEADO*. Asigna `RP-2026-000001`.
  - `CDP *`: Selecciona el CDP de donde sale la plata.
  - `Tercero *`: Selecciona el proveedor (Ej: *Químicos del Caribe S.A.S.*).
  - `Monto *`: Valor del contrato (Ej: `60000000`).
  - `Objeto del contrato *`: Número de contrato y descripción.

=== 3.5 Obligaciones Presupuestales
- *¿Dónde está?* Menú lateral > *Presupuesto de Gastos* > *Obligaciones*.
- *¿Para qué sirve?* Reconoce la deuda cuando el proveedor entrega las cosas y radica la factura.
- *Crear:* Botón *Crear Obligación*.
  - 🛑 `Número de Obligación`: *BLOQUEADO*. Asigna `OBL-2026-000001`.
  - `Registro Presupuestal (RP) *`: Selecciona el RP del proveedor.
  - `Tipo de soporte *`: Escribe `Factura Electrónica`.
  - `Número de soporte *`: *AQUÍ ESCRIBES TÚ*. Escribe el número que dice la factura (Ej: `FQ-4589`).
  - `Monto *`: Valor exacto de la factura (Ej: `25000000`).
  - `Descripción`: Detalle del cumplimiento.

#pagebreak()

// ==========================================
// SECCIÓN 4: TESORERÍA Y BANCOS
// ==========================================
#heading(level: 1)[4. Tesorería y Bancos]

=== 4.1 Programación Anual de Caja (PAC)
- *¿Dónde está?* Menú lateral > *Tesorería* > *Programación Anual de Caja*.
- *¿Para qué sirve?* Define el cupo máximo de plata que se puede pagar en cada mes del año para no dejar la cuenta de banco en ceros.

=== 4.2 Órdenes de Pago
- *¿Dónde está?* Menú lateral > *Tesorería* > *Órdenes de pago*.
- *¿Para qué sirve?* Es la autorización formal del Gerente al Tesorero para hacer el giro.
- *Crear:* Botón *Crear Orden de pago*.
  - 🛑 `Número`: Asignado solo (`OP-2026-000001`).
  - `Obligación *`: Selecciona la factura radicada aprobada.
  - `Cuenta bancaria *`: De qué banco saldrá la plata (Ej: *Bancolombia Corriente*).
  - `Medio de pago *`: *Transferencia bancaria*.
  - `Monto a pagar *`: Valor a girar.
- *Acción Ejecutar Pago:* En la tabla, botón azul *Ejecutar Pago*. Pide la fecha y el número de confirmación bancaria (Ej: `TR-984218`). Al confirmar, se descuenta del banco y crea el Egreso automáticamente.

=== 4.3 Pagos / Egresos
- *¿Dónde está?* Menú lateral > *Tesorería* > *Pagos*.
- *¿Para qué sirve?* Lista todos los giros y egresos contables realizados. Muestra si están conciliados con el extracto bancario.

=== 4.4 Caja Menor
- *¿Dónde está?* Menú lateral > *Tesorería* > *Caja Menor*.
- *¿Para qué sirve?* Manejo de plata en efectivo para gastos diarios urgentes (taxis, tintos, fotocopias).
- *Crear Fondo:* Botón *Crear Fondo* (Nombre, Responsable, Monto Ej: `3000000`, Cuenta de banco de origen).
- *Movimientos de gasto:* Entra al fondo > Pestaña *Movimientos* > Botón *Nuevo Movimiento* (Fecha, Beneficiario, Recibo de la tienda Ej: `Recibo 045`, Monto Ej: `35000`, Descripción).

=== 4.5 Importar Extracto Bancario y Conciliación
- *¿Dónde está?* Menú lateral > *Tesorería* > *Importar Extracto Bancario*.
- *¿Para qué sirve?* Sube el archivo Excel o CSV que descargas de la página del banco para cruzar las transacciones contra la contabilidad y encontrar descuadres.

#v(8pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// SECCIÓN 5: OPERACIÓN DIARIA
// ==========================================
#heading(level: 1)[5. Operación Diaria (Causaciones y Cotizaciones)]

=== 5.1 Ingresos (Causación de Facturación)
- *¿Dónde está?* Menú lateral > *Operación* > *Ingresos*.
- *¿Para qué sirve?* Para meter una factura que nosotros emitimos a un cliente o un cobro de subsidio.
- *Campos:* `Tercero *`, `Fecha *`, `Cuenta PUC Ingreso (Clase 4) *`, `Cuenta PUC Cobro (Clase 13) *`, `Número de soporte *` (Ej: `FV-001`), `Valor *`.

=== 5.2 Egresos (Causación de Gastos Operativos)
- *¿Dónde está?* Menú lateral > *Operación* > *Egresos*.
- *¿Para qué sirve?* Para meter gastos directos con cálculo automático de Retención en la Fuente.
- *Campos:* `Tercero *`, `Fecha *`, `Cuenta de gasto (Clase 5) *`, `Cuenta por pagar (Clase 23) *`, `Número de factura del proveedor *`, `Valor *`.

=== 5.3 Cotizaciones Comerciales
- *¿Dónde está?* Menú lateral > *Operación* > *Cotizaciones*.
- *¿Para qué sirve?* Para cotizarle a un cliente un servicio (Ej: instalación de acometidas).
- *Acción Convertir a Ingreso:* Cuando el cliente acepta la cotización, en la tabla presionas el botón *Convertir a Ingreso* y el sistema crea la cuenta por cobrar automáticamente sin repetir datos.

#pagebreak()

// ==========================================
// SECCIÓN 6: CONTROL Y COMPROBANTES
// ==========================================
#heading(level: 1)[6. Control y Comprobantes Contables]

=== 6.1 Periodos Contables (Abrir / Cerrar Meses)
- *¿Dónde está?* Menú lateral > *Control* > *Periodos contables*.
- *¿Para qué sirve?* Si un mes ya terminó y se pagaron impuestos, el Contador lo cambia a estado *Cerrado*. Esto impide que cualquier usuario meta facturas viejas y descuadre las declaraciones.

=== 6.2 Comprobantes Contables (Vouchers)
- *¿Dónde está?* Menú lateral > *Control* > *Comprobantes*.
- *¿Para qué sirve?* Es el libro contable de la empresa. Aquí viven todos los asientos (Ingresos, Egresos, Pagos, Notas de Ajuste).
- *Crear Comprobante Manual:* Botón *Crear Comprobante*.
  - 🛑 `Número`: *BLOQUEADO*. El sistema le pone `ING-`, `EGR-` o `AJU-` según el tipo.
  - `Tipo de comprobante *`: *Ingreso*, *Egreso*, *Pago* o *Ajuste*.
  - `Fecha *`: Día del movimiento.
  - `Tercero`: A nombre de quién.
  - `Descripción *`: Explicación clara.
  - *Asientos Contables:* En la tabla de abajo agregas las cuentas PUC. Recuerda: *La suma de la columna Débito debe ser idéntica a la suma de la columna Crédito*. Si no cuadra, no te deja guardar.
- *Acción Anular / Ajustar:* Los comprobantes aprobados NO se borran por ley. Si cometiste un error, haz clic en *Crear Nota de Ajuste* para reversar el movimiento de forma transparente.

#v(8pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// SECCIÓN 7: CATÁLOGOS MAESTROS
// ==========================================
#heading(level: 1)[7. Catálogos Maestros]

=== 7.1 Terceros
- *¿Dónde está?* Menú lateral > *Catálogos* > *Terceros*.
- *Crear:* Botón *Crear Tercero* (`Tipo Persona`, `Tipo Documento`, `Número NIT/Cédula`, `Dígito Verificación`, `Nombre`, `Ciudad`, `Teléfono`, `Correo`).

=== 7.2 Plan de Cuentas (PUC)
- *¿Dónde está?* Menú lateral > *Catálogos* > *Plan de cuentas*.
- *Uso:* Lista jerárquica de cuentas contables. `Código PUC *` (Ej: `110505`), `Nombre *` (Ej: *Caja General*), `Naturaleza *` (*Débito* o *Crédito*).

=== 7.3 Caja y Bancos
- *¿Dónde está?* Menú lateral > *Catálogos* > *Caja y bancos*.
- *Uso:* Registra las cuentas corrientes o de ahorros de la empresa (`Nombre`, `Banco`, `Número de cuenta`, `Cuenta PUC asociada`).

=== 7.4 Reglas de Retención
- *¿Dónde está?* Menú lateral > *Catálogos* > *Retenciones*.
- *Uso:* Configura la Retención en la Fuente, ReteIVA y ReteICA (`Concepto`, `Tarifa %`, `Base Mínima en Pesos`, `Cuenta PUC de Pasivo`).

=== 7.5 Almacenes y Dependencias
- *Almacenes:* Menú > *Catálogos* > *Almacenes* (Registra las bodegas físicas).
- *Dependencias:* Menú > *Catálogos* > *Dependencias* (Registra oficinas: *Gerencia*, *Dirección Técnica*, *Comercial*).

#pagebreak()

// ==========================================
// SECCIÓN 8: ALMACÉN E INVENTARIOS
// ==========================================
#heading(level: 1)[8. Almacén e Inventarios]

=== 8.1 Elementos / Artículos
- *¿Dónde está?* Menú lateral > *Catálogos* > *Elementos*.
- *Crear Artículo:* Botón *Crear Elemento*.
  - `Código`: *Opcional*. Si lo dejas vacío, el sistema le asigna `ART-00001` solo.
  - `Nombre *`: Ej: *Hipoclorito de Sodio al 15%*.
  - `Tipo *`: *Consumible* (se gasta) o *Devolutivo* (herramienta que se devuelve).
  - `Unidad de medida *`: *Galón*, *Kilo*, *Unidad*, *Caja*.
  - `Stock mínimo`: Alerta de cantidad mínima.

=== 8.2 Movimientos de Almacén (Entradas, Salidas, Traslados)
- *¿Dónde está?* Menú lateral > *Almacén* > *Movimientos*.
- *Entrada por compra:* `Tipo: Entrada`, selecciona Almacén y Proveedor. Pulsa *Crear*. En las líneas añade el producto, cantidad y costo unitario.
- *Salida para consumo:* `Tipo: Salida`, selecciona Almacén y Dependencia destino (Ej: *Dirección Técnica*). En las líneas añade qué materiales se llevaron.
- *Traslado:* Mueve productos de una bodega a otra.

=== 8.3 Reportes de Inventario en Vivo
- *Stock de Elementos:* Menú > *Almacén* > *Stock de elementos*. Muestra cuánto hay de cada producto en cada bodega en este momento.
- *Auxiliar de Elementos (Kardex):* Menú > *Almacén* > *Auxiliar de elementos*. Muestra cada entrada y salida con fecha y responsable.

#v(8pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// SECCIÓN 9: RECURSOS HUMANOS Y NÓMINA
// ==========================================
#heading(level: 1)[9. Recursos Humanos y Nómina]

=== 9.1 Fondos de Seguridad Social
- *¿Dónde está?* Menú lateral > *Nómina* > *Fondos*.
- *Uso:* Directorio de EPS, Fondos de Pensiones, Cesantías y ARL con su NIT y código oficial.

=== 9.2 Empleados
- *¿Dónde está?* Menú lateral > *Nómina* > *Empleados*.
- *Crear:* Cédula, Nombres, Cargo, Salario Básico mensual, Dependencia y selección de EPS y Fondo de Pensión.

=== 9.3 Conceptos de Nómina
- *¿Dónde está?* Menú lateral > *Nómina* > *Conceptos*.
- *Uso:* Salario básico, Auxilio de transporte, Horas extras, Descuentos de salud (4%) y pensión (4%).

#pagebreak()

// ==========================================
// SECCIÓN 10: REPORTES Y ESTADOS FINANCIEROS
// ==========================================
#heading(level: 1)[10. Reportes y Estados Financieros (Exportables a Excel)]
#text(fill: rgb("#64748b"), style: "italic", [Todos estos reportes tienen un botón verde arriba a la derecha que dice "Exportar a Excel". Haz clic en él y en 2 segundos tienes el archivo .xlsx descargado en tu computador.])

#v(4pt)
1. *Balance de Prueba (Menú > Reportes > Balance):* Es el reporte rey. Muestra todas las cuentas del PUC con saldo inicial, débitos, créditos y saldo final. Sirve para revisar que las cuentas estén cuadradas.
2. *Balance General (Menú > Reportes > Balance general):* Muestra el Estado de Situación Financiera de la empresa (Activos = Pasivos + Patrimonio).
3. *Estado de Resultados (Menú > Reportes > Estado de resultados):* Muestra cuánto dinero vendió la empresa en el mes menos los costos y gastos, dando la Utilidad o Pérdida.
4. *Libro Mayor (Menú > Reportes > Libro mayor):* Libro oficial exigido por la DIAN y la Cámara de Comercio.
5. *Libro Diario (Menú > Reportes > Libro diario):* Registro cronológico día por día de cada asiento contable.
6. *Libro Auxiliar (Menú > Reportes > Libro auxiliar):* Detalle de cada movimiento de una cuenta contable específica.
7. *Cartera de Clientes (Menú > Cuentas x Cobrar > Cartera de clientes):* Muestra qué clientes o entidades nos deben dinero y cuántos días llevan de vencidos.
8. *Cuentas por Pagar (Menú > Reportes > Cuentas por pagar):* Lista de todas las facturas de proveedores que tenemos pendientes por pagar.
9. *Ejecución Presupuestal (Menú > Reportes > Ejecución Presupuestal):* Informe de cómo va el presupuesto de gastos (Apropiado vs Comprometido con RPs vs Obligado vs Pagado).

#v(8pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// SECCIÓN 11: CONFIGURACIÓN Y AUDITORÍA
// ==========================================
#heading(level: 1)[11. Configuración, Seguridad y Auditoría]

=== 11.1 Datos de la Empresa
- *¿Dónde está?* Menú lateral > *Configuración* > *Datos de la Empresa*.
- *Uso:* Razón social (*Aguas de Sucre S.A. E.S.P.*), NIT (*900247655-1*), Representante legal, Códigos DANE (`70001` Sincelejo) y activación del control presupuestal.

=== 11.2 Responsables Firmantes
- *¿Dónde está?* Menú lateral > *Configuración* > *Responsables firmantes*.
- *Uso:* Nombres y tarjetas profesionales del Gerente, Contador y Revisor Fiscal para las firmas de balances.

=== 11.3 Usuarios
- *¿Dónde está?* Menú lateral > *Configuración* > *Usuarios*.
- *Uso:* Crear cuentas de acceso con correo y contraseña para los funcionarios de la oficina.

=== 11.4 Pista de Auditoría (AuditLogs)
- *¿Dónde está?* Menú lateral > *Configuración* > *Pista de Auditoría*.
- *¿Para qué sirve?* Muestra un registro de TODO lo que ocurre en el sistema: quién creó un comprobante, quién modificó un tercero, a qué hora exacta y desde qué computador. *Aquí nada se puede hacer a escondidas*.

#v(14pt)
#success-callout("¡FELICITACIONES! YA CONOCES EL 100% DE CONTPASS", [
  Con este manual en tus manos, tienes el control total de cada botón, cada pantalla y cada flujo de la empresa. Imprímelo, consérvalo cerca de tu teclado y consúltalo cada vez que tengas una duda.
])

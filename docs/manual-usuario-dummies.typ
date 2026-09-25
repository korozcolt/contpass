#set document(title: "Manual de Uso ContPass (Para Dummies)", author: "ContPass Team")
#set page(
  paper: "a4",
  margin: (top: 2.5cm, bottom: 2.5cm, left: 2.5cm, right: 2.5cm),
  header: context {
    if counter(page).get().first() > 1 [
      #grid(
        columns: (1fr, auto),
        align: (left + horizon, right + horizon),
        text(size: 8.5pt, fill: rgb("#64748b"), weight: "bold", [CONTPASS · MANUAL DE OPERACIÓN OFICIAL]),
        text(size: 8.5pt, fill: rgb("#94a3b8"), [Guía Paso a Paso])
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
        text(size: 8pt, fill: rgb("#94a3b8"), [ContPass ERP · Entorno UAT / Producción]),
        text(size: 8.5pt, fill: rgb("#64748b"), weight: "bold", [Página #counter(page).display()])
      )
    ]
  }
)

#set text(font: "Helvetica", size: 10pt, fill: rgb("#1e293b"), lang: "es")
#set par(justify: true, leading: 0.65em)

// --- ESTILOS DE CAJAS Y ALERTAS ---
#let callout(title, body, color: rgb("#0284c7"), bg: rgb("#f0f9ff")) = {
  rect(
    width: 100%,
    radius: 6pt,
    fill: bg,
    stroke: 1pt + color,
    inset: (x: 12pt, y: 10pt),
    outset: 0pt,
    [
      #text(weight: "bold", fill: color, size: 10.5pt, title) \
      #v(2pt)
      #text(size: 9.5pt, body)
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
  #v(2cm)
  #image("/public/images/brand/contpass-logo-horizontal.png", width: 55%)
  
  #v(2.5cm)
  #text(size: 26pt, weight: "black", fill: rgb("#0f172a"), [MANUAL DE USO OFICIAL]) \
  #v(4pt)
  #text(size: 16pt, weight: "bold", fill: rgb("#0284c7"), [GUÍA PASO A PASO (A PRUEBA DE ERRORES)]) \
  #v(8pt)
  #text(size: 11pt, fill: rgb("#64748b"), [Instrucciones claras, directas y sin tecnicismos para operar todos los flujos de la empresa en el día a día.])

  #v(3.5cm)
  #rect(
    width: 85%,
    radius: 8pt,
    fill: rgb("#f8fafc"),
    stroke: 1pt + rgb("#cbd5e1"),
    inset: 14pt,
    [
      #text(weight: "bold", size: 11pt, fill: rgb("#0f172a"), [Entorno de Capacitación y Pruebas:]) \
      #v(2pt)
      #text(size: 10pt, fill: rgb("#334155"), [Cliente Piloto: *Aguas de Sucre S.A. E.S.P.*]) \
      #text(size: 10pt, fill: rgb("#334155"), [Plataforma: #link("https://contpass.kor-bytes.com/admin")[#strong("https://contpass.kor-bytes.com/admin")]]) \
      #text(size: 9.5pt, fill: rgb("#64748b"), [Vigencia Fiscal: 2026 · Versión del Manual: 2.0 Definitiva])
    ]
  )
]

#pagebreak()

// ==========================================
// REGLAS DE ORO
// ==========================================
#heading(numbering: none)[Reglas de Oro para Sobrevivir al Sistema]
#v(6pt)
#text(size: 10.5pt, [Antes de tocar cualquier tecla o mover el mouse, memoriza estas 4 reglas fundamentales:])

#v(8pt)
#danger-callout("REGLA 1: Las casillas grises NO se tocan", [
  Si ves una casilla con texto gris clarito que no te deja hacer clic ni escribir, *no insistas, no le des doble clic y no digas que el sistema está dañado*. \
  El sistema calcula ese número por sí solo (como los comprobantes `ING-`, `EGR-`, o los certificados `CDP-`, `RP-`, `OP-`) y se lo asignará automáticamente cuando guardes.
])

#v(6pt)
#warning-callout("REGLA 2: Si tiene asterisco rojo (*), es OBLIGATORIA", [
  Si dejas vacía una casilla que tiene un asterisco rojo e intentas guardar, la pantalla no avanzará y te saldrá un letrero en rojo. Tienes que llenarla sí o sí para poder continuar.
])

#v(6pt)
#callout("REGLA 3: Si no pulsas el botón 'Crear' o 'Guardar', NO se guarda nada", [
  El sistema no lee tu mente ni guarda automáticamente mientras escribes. Siempre debes bajar al final de la pantalla y presionar el botón de fondo negro o azul que dice *Crear* o *Guardar cambios*.
])

#v(6pt)
#success-callout("REGLA 4: No inventes códigos raros", [
  Donde el sistema te muestre una lista desplegable con una flechita, haz clic y escoge una opción de la lista. No intentes escribir palabras inventadas.
])

#v(14pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))
#v(8pt)

// ==========================================
// CÓMO ENTRAR AL SISTEMA
// ==========================================
#heading(level: 2)[Paso Cero: Cómo Iniciar Sesión]

1. Abre tu navegador web (Google Chrome es el más recomendado).
2. En la barra superior de direcciones, escribe exactamente: \
   #text(weight: "bold", fill: rgb("#0284c7"), "https://contpass.kor-bytes.com/admin/login") y presiona *Enter*.
3. En la casilla *Correo electrónico*, escribe tu usuario:
   - Para perfil Administrador: `admin@aguasdesucre.com.co`
   - Para perfil Contador: `contador@aguasdesucre.com.co`
4. En la casilla *Contraseña*, escribe exactamente: `AguasDeSucre2026*`
5. Haz clic en el botón negro que dice *Iniciar sesión*.
6. ¡Listo! Ya estás adentro del sistema. A la izquierda verás la columna con todo el menú de opciones.

#pagebreak()

// ==========================================
// FLUJO 1: REGISTRAR UN PROVEEDOR (TERCERO)
// ==========================================
#heading(level: 1)[Flujo 1: Registrar un Proveedor o Contratista Nuevo]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Antes de poder pagarle a alguien, hacerle un contrato o comprarle un tornillo, esa persona o empresa debe estar registrada en el sistema.])

#v(6pt)
#callout("¿Cómo llego con el mouse?", [
  1. Mira la barra lateral gris a la izquierda. \
  2. Baja hasta donde dice *Catálogos*. \
  3. Haz clic en *Terceros*. \
  4. Arriba a la derecha, haz clic en el botón negro *Crear Tercero*.
])

#v(6pt)
#table(
  columns: (1.5fr, 1.2fr, 2.5fr),
  fill: (x, y) => if y == 0 { rgb("#0f172a") } else if calc.even(y) { rgb("#f8fafc") } else { white },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 7pt,
  table.header(
    text(fill: white, weight: "bold", [Nombre de la Casilla]),
    text(fill: white, weight: "bold", [Tipo de Campo]),
    text(fill: white, weight: "bold", [¿Qué tengo que escribir aquí?])
  ),
  [Tipo de persona \*], [Desplegable], [Escoge *Persona Jurídica* (empresas) o *Persona Natural* (personas con cédula).],
  [Tipo de documento \*], [Desplegable], [Selecciona *NIT* o *Cédula de Ciudadanía*.],
  [Número de documento \*], [Texto], [Escribe el número *sin puntos ni guiones*. Ej: `900247655`.],
  [Dígito de verificación], [Texto], [El numerito después del guion del RUT (Ej: `1`). El sistema lo valida.],
  [Nombre / Razón Social \*], [Texto], [El nombre completo (Ej: *Químicos del Caribe S.A.S.* o *Juan Pérez*).],
  [Ciudad], [Texto], [Ciudad donde está ubicado (Ej: *Sincelejo*).],
  [Dirección / Teléfono / Correo], [Texto], [Datos de contacto para enviarle comprobantes oficiales.]
)

#v(4pt)
👉 *Para terminar:* Baja al fondo de la pantalla y pulsa el botón *Crear*.

#v(14pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// FLUJO 2: EXPEDIR UN CDP
// ==========================================
#heading(level: 1)[Flujo 2: Apartar Dinero del Presupuesto (CDP)]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Antes de sacar una licitación o firmar un contrato, la ley exige congelar la plata para asegurar que la empresa sí tiene fondos.])

#v(6pt)
#callout("¿Cómo llego con el mouse?", [
  1. En el menú de la izquierda, busca el título *Presupuesto de Gastos*. \
  2. Haz clic en *Certificados (CDP)*. \
  3. Arriba a la derecha, haz clic en el botón negro *Crear Certificado de disponibilidad*.
])

#v(6pt)
#table(
  columns: (1.5fr, 1.2fr, 2.5fr),
  fill: (x, y) => if y == 0 { rgb("#0f172a") } else if calc.even(y) { rgb("#f8fafc") } else { white },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 7pt,
  table.header(
    text(fill: white, weight: "bold", [Nombre de la Casilla]),
    text(fill: white, weight: "bold", [Estado]),
    text(fill: white, weight: "bold", [¿Qué significa y qué hago?])
  ),
  [Número de CDP], [🛑 BLOQUEADO], [Dice *(Asignado automáticamente al guardar)*. ¡No lo toques! El sistema le pondrá `CDP-2026-000001` solito.],
  [Rubro presupuestal \*], [Desplegable], [Haz clic y escoge la bolsa de dinero de donde saldrá la plata (Ej: `2.1.2.02.01 · Compra de Químicos`). Al lado verás cuánto saldo disponible queda.],
  [Fecha de emisión \*], [Calendario], [Selecciona la fecha de hoy.],
  [Monto del CDP \*], [Moneda], [Escribe la plata total que vas a apartar sin puntos (Ej: `85000000` para 85 millones).],
  [Objeto del gasto \*], [Texto largo], [Escribe claramente para qué es la plata (Ej: *Suministro de hipoclorito y reactivos para plantas de agua potable*).]
)

#v(4pt)
👉 *Para terminar:* Baja y haz clic en el botón *Crear*.

#pagebreak()

// ==========================================
// FLUJO 3: EXPEDIR UN RP
// ==========================================
#heading(level: 1)[Flujo 3: Amarrar el Contrato al Proveedor (RP)]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Cuando ya se firmó el contrato con el ganador. La plata apartada en el CDP queda congelada a su nombre exclusivo.])

#v(6pt)
#callout("¿Cómo llego con el mouse?", [
  1. Menú izquierdo > *Presupuesto de Gastos*. \
  2. Haz clic en *Registros (RP)*. \
  3. Arriba a la derecha, haz clic en *Crear Registro presupuestal*.
])

#v(6pt)
#table(
  columns: (1.5fr, 1.2fr, 2.5fr),
  fill: (x, y) => if y == 0 { rgb("#0f172a") } else if calc.even(y) { rgb("#f8fafc") } else { white },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 7pt,
  table.header(
    text(fill: white, weight: "bold", [Casilla]),
    text(fill: white, weight: "bold", [Estado]),
    text(fill: white, weight: "bold", [Instrucción])
  ),
  [Número de RP], [🛑 BLOQUEADO], [No lo toques. El sistema le pondrá `RP-2026-000001` de forma correlativa.],
  [CDP \*], [Desplegable], [Escoge el CDP de donde viene la plata. El sistema te muestra el saldo restante.],
  [Tercero \*], [Desplegable], [Escoge al contratista que ganó el contrato (Ej: *Químicos del Caribe S.A.S.*).],
  [Fecha de emisión \*], [Calendario], [Fecha de firma del contrato.],
  [Monto del RP \*], [Moneda], [Valor del contrato (Ej: `60000000`). No puede superar el saldo del CDP.],
  [Objeto del contrato \*], [Texto largo], [Número de contrato y objeto (Ej: *Contrato 045-2026 Suministro de cloro*).]
)

#v(4pt)
👉 *Para terminar:* Pulsa el botón *Crear*.

#v(14pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// FLUJO 4: OBLIGACIÓN (METER LA FACTURA)
// ==========================================
#heading(level: 1)[Flujo 4: Registrar la Factura del Proveedor (Obligación)]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Cuando el proveedor ya entregó las cosas a satisfacción y te mandó la factura de cobro.])

#v(6pt)
#callout("¿Cómo llego con el mouse?", [
  1. Menú izquierdo > *Presupuesto de Gastos*. \
  2. Haz clic en *Obligaciones*. \
  3. Clic arriba a la derecha en *Crear Obligación*.
])

#v(6pt)
#table(
  columns: (1.5fr, 1.2fr, 2.5fr),
  fill: (x, y) => if y == 0 { rgb("#0f172a") } else if calc.even(y) { rgb("#f8fafc") } else { white },
  stroke: 0.5pt + rgb("#cbd5e1"),
  inset: 7pt,
  table.header(
    text(fill: white, weight: "bold", [Casilla]),
    text(fill: white, weight: "bold", [Tipo]),
    text(fill: white, weight: "bold", [Instrucción])
  ),
  [Número de Obligación], [🛑 BLOQUEADO], [El sistema le pondrá su código `OBL-2026-000001` automáticamente.],
  [Registro Presupuestal \*], [Desplegable], [Escoge el RP del contrato al que le vas a pagar la factura.],
  [Tipo de soporte \*], [Texto], [Escribe `Factura Electrónica` o `Cuenta de Cobro`.],
  [Número de soporte \*], [Texto], [🔴 *AQUÍ SÍ ESCRIBES TÚ:* Copia el número exacto que dice la factura de papel o el PDF del proveedor (Ej: `FQ-4589`).],
  [Fecha de causación \*], [Calendario], [Fecha en que recibiste la factura.],
  [Monto de la obligación \*], [Moneda], [Valor exacto a pagar en la factura (Ej: `25000000`).],
  [Descripción], [Texto], [Resumen (Ej: *Entrega primera tanda de cloro según acta de recibo*).]
)

#v(4pt)
👉 *Para terminar:* Pulsa el botón *Crear*.

#pagebreak()

// ==========================================
// FLUJO 5: PAGAR POR EL BANCO
// ==========================================
#heading(level: 1)[Flujo 5: Autorizar y Pagar por el Banco (Tesorería)]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Cuando el jefe ordena transferirle la plata al proveedor para cancelar la factura.])

#v(6pt)
#callout("Paso A: Crear la Orden de Pago (OP)", [
  1. Menú izquierdo > *Tesorería* > Clic en *Órdenes de pago*. \
  2. Clic en *Crear Orden de pago*. \
  3. Casillas: \
     - 🛑 `Número de Orden de Pago`: Dejar quieto. El sistema le pone `OP-2026-000001`. \
     - `Obligación`: Selecciona la factura aprobada que vas a pagar. \
     - `Cuenta bancaria`: Selecciona de qué banco saldrá la plata (Ej: *Bancolombia Corriente*). \
     - `Medio de pago`: Selecciona *Transferencia bancaria*. \
     - `Monto a pagar`: Valor a girar. \
  4. Clic en *Crear*.
])

#v(8pt)
#success-callout("Paso B: Ejecutar el Pago Real (Sale la plata del banco)", [
  1. En la lista de Órdenes de Pago, busca la orden que acabas de crear. \
  2. Al lado derecho verás un botón que dice *Ejecutar Pago*. Haz clic sobre él. \
  3. En la ventanita emergente: \
     - `Fecha de pago`: La fecha de hoy. \
     - `Referencia / Aprobación`: Escribe el número de transferencia que te dio la página del banco (Ej: `TR-984218`). \
  4. Haz clic en el botón azul *Confirmar / Ejecutar*. \
  \
  🎉 *¡Listo!* En ese segundo el sistema descuenta la plata de la cuenta de banco y crea el *Comprobante de Egreso oficial* (`EGR-2026-000001`) sin que tengas que saber contabilidad.
])

#v(14pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// FLUJO 6: ENTRADA DE MATERIALES A BODEGA
// ==========================================
#heading(level: 1)[Flujo 6: Llegaron Materiales al Almacén (Inventarios)]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Cuando el camión del proveedor descarga químicos, tubos o papelería en la bodega.])

#v(6pt)
#callout("¿Cómo llego con el mouse?", [
  1. Menú izquierdo > *Almacén* > Clic en *Movimientos de Almacén*. \
  2. Clic arriba a la derecha en *Crear Movimiento de almacén*.
])

#v(6pt)
1. *Encabezado del movimiento:*
   - `Tipo de movimiento`: Selecciona *Entrada*.
   - `Almacén`: Selecciona la bodega donde se guardará (Ej: *Bodega Central Planta Sincelejo*).
   - `Proveedor`: Selecciona quién lo vendió (Ej: *Químicos del Caribe S.A.S.*).
   - `Fecha`: Fecha de hoy.
   - `Descripción`: Ej: *Llegada de 20 canecas de hipoclorito y 10 rollos de teflón*.
   - Pulsa el botón *Crear*.
2. *Agregar los productos:*
   - Baja a la sección que dice *Líneas* y pulsa *Añadir a Líneas*.
   - `Artículo`: Escoge el producto de la lista (Ej: *Hipoclorito de Sodio al 15%*).
   - `Cantidad`: Cuántas unidades entraron (Ej: `20`).
   - `Costo unitario`: Cuánto costó cada una (Ej: `150000`).
   - Pulsa *Guardar*. El inventario queda actualizado de inmediato.

#pagebreak()

// ==========================================
// FLUJO 7: SALIDA DE MATERIALES
// ==========================================
#heading(level: 1)[Flujo 7: Entregar Materiales para una Reparación (Salida)]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Cuando una cuadrilla o fontanero te pide tubos o válvulas para arreglar un daño en la calle.])

#v(6pt)
#callout("¿Cómo llego con el mouse?", [
  1. Menú izquierdo > *Almacén* > *Movimientos de Almacén*. \
  2. Clic en *Crear Movimiento de almacén*.
])

#v(6pt)
1. `Tipo de movimiento`: Selecciona *Salida*.
2. `Almacén`: De qué bodega se sacan los materiales.
3. `Dependencia destino`: Qué cuadrilla o área se lo lleva (Ej: *Dirección Técnica y Operativa*).
4. `Fecha`: Fecha de entrega.
5. `Descripción`: Para qué es (Ej: *Salida de 2 tubos para reparar fuga en Calle 20 con Cra 18*).
6. Pulsa *Crear*.
7. Agrega el artículo entregado:
   - `Artículo`: *Tubería PVC 4" RDE 21*.
   - `Cantidad`: `2`.
   - Pulsa *Guardar*. El inventario se resta automáticamente.

#v(14pt)
#line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))

// ==========================================
// FLUJO 8: SACAR REPORTES EN EXCEL
// ==========================================
#heading(level: 1)[Flujo 8: Sacar Reportes en Excel en 2 Clics]
#text(fill: rgb("#64748b"), style: "italic", [¿Cuándo se hace esto? Cuando el Gerente, el Contador o el Revisor Fiscal te piden las cuentas para una reunión inmediata.])

#v(6pt)
#callout("Instrucciones rápidas:", [
  1. En el menú de la izquierda, baja hasta la sección *Contabilidad*. \
  2. Haz clic en la opción que te pidieron: \
     - *Balance de Prueba* (el reporte más solicitado por todos). \
     - *Libro Mayor y Balances*. \
     - *Libro Diario*. \
  3. Verás una tabla grande con todos los números. \
  4. Arriba a la derecha de la tabla, busca el botón verde con icono de hoja de cálculo que dice *Exportar a Excel*. \
  5. Haz clic sobre él. \
  6. En 2 segundos se descargará a tu computador un archivo `.xlsx`. Ábrelo o envíalo por correo.
])

#v(18pt)
#danger-callout("¿QUÉ HAGO SI ME SALE UN ERROR?", [
  - *Error: 'El comprobante no está balanceado':* Revisa los números. La suma de la columna izquierda (Débito) tiene que ser exactamente igual a la columna derecha (Crédito).
  - *Error: 'Periodo contable cerrado':* La fecha que pusiste pertenece a un mes cerrado por auditoría. Habla con el Contador para que te verifique la fecha.
  - *No me deja guardar:* Mira arriba en la pantalla. Alguna casilla obligatoria con asterisco rojo quedó vacía.
])

# Phase 7: Cuentas por pagar — alcance mercado privado - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-18
**Phase:** 07-cuentas-por-pagar-mercado-privado
**Areas discussed:** Tracking de "pagado" — alcance del fix, Columna "Obligación" para filas de mercado privado, Valor a reportar — bruto o neto de retención

---

## Tracking de "pagado" — alcance del fix

| Option | Description | Selected |
|--------|-------------|----------|
| Solo mercado privado | Agregar ExpenseRecords privados usando source_voucher_id (correcto, igual que AccountsReceivable). Dejar intacta la lógica de BudgetObligation/payment_order_id tal cual está hoy, aunque probablemente ya esté rota. Calza exacto con el gap del audit, mínimo riesgo para el reporte que usa Aguas de Sucre hoy. | ✓ |
| Unificar todo el cálculo de "pagado" | Reescribir AccountsPayable::openItems() para que TODOS los ExpenseRecords calculen "pagado" vía source_voucher_id. Corrige el bug latente en el flujo público como efecto colateral, pero cambia comportamiento de un reporte que ya usa un cliente real. | |

**User's choice:** Solo mercado privado (recomendado).
**Notes:** El scouting de código (grep en `app/`) confirmó que `payment_order_id` nunca se setea en ningún servicio — solo se lee en `AccountsPayable::openItems()`. `RegisterPayment` (único creador de `Payment`) solo setea `source_voucher_id`. Esto significa que "pagado" probablemente ya muestra 0 siempre para obligaciones públicas — un bug pre-existente, no introducido por esta milestone. Registrado como GitHub Issue #3 en lugar de arreglarse silenciosamente o ignorarse, por la política de "documentar todo" de CLAUDE.md.

---

## Columna "Obligación" para filas de mercado privado

| Option | Description | Selected |
|--------|-------------|----------|
| Número de comprobante/voucher | Igual que hace Cartera de Clientes ("Comprobante" = income.voucher.number) para su columna análoga. Siempre hay un voucher. | ✓ |
| Vacío / "—" | Dejar la celda en blanco ya que literalmente no aplica el concepto de "obligación presupuestal". | |

**User's choice:** Número de comprobante/voucher (recomendado).
**Notes:** Consistente con el patrón ya establecido por `AccountsReceivable`.

---

## Valor a reportar — bruto o neto de retención

| Option | Description | Selected |
|--------|-------------|----------|
| Neto de retención: amount - withholding_amount | Es lo real y efectivamente adeudado/pagable al tercero — coincide con el crédito a payable_account_id que arma PostExpenseVoucher. | ✓ |
| Bruto: amount tal cual | Consistente con cómo BudgetObligation.amount se usa hoy en AccountsPayable, aunque no refleje lo pagable al tercero. | |

**User's choice:** Neto de retención (recomendado).
**Notes:** Confirmado leyendo `PostExpenseVoucher.php` (líneas 60-75) — `payable_account_id` recibe exactamente `amount - withholding_amount`.

---

## Claude's Discretion

- Estructura interna de `AccountsPayable::openItems()` para combinar ambas fuentes (merge de Collections vs. método privado nuevo fusionado).
- Nombres exactos de variables/métodos privados nuevos.
- Reuso del método `bucket()` existente para las nuevas filas (no duplicar lógica de buckets).

## Deferred Ideas

- Unificar el cálculo de "pagado" para TODAS las obligaciones (públicas y privadas) — documentado como GitHub Issue #3, fuera de esta fase.
- Extraer el método `bucket()` duplicado entre `AccountsPayable` y `AccountsReceivable` a un helper compartido — no pedido, scope creep, no se toca.

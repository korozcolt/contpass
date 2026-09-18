# Phase 7: Cuentas por pagar — alcance mercado privado - Context

**Gathered:** 2026-09-18
**Status:** Ready for planning

<domain>
## Phase Boundary

Extender el reporte/export "cuentas por pagar" (pantalla Filament, CSV y Excel) para que también incluya `ExpenseRecord`s de mercado privado (sin `BudgetObligation` asociada, `budget_obligation_id` nulo) — no solo obligaciones presupuestales públicas. Cierra el gap de flujo `accounts-payable-private-market-scope` del audit v1.0, requirement `AP-01`.

**Carried forward from Phase 5 (Exportación Excel):** D-04 estableció que cada reporte tiene un método privado compartido (`*Rows()` en `AccountingReportController`) que arma headers+filas UNA sola vez, reusado por CSV y Excel sin lógica de consulta duplicada — `accountsPayableRows()` ya existe y llama a `AccountsPayable::openItems()`; esta fase solo cambia lo que ese método retorna. D-07 estableció que las fechas viajan como objeto `Carbon` crudo hacia el export Excel — igual aplica aquí (`accrual_date` ya es Carbon en ambos servicios hermanos).

</domain>

<decisions>
## Implementation Decisions

### Alcance del fix de "pagado" (D-01)
- **D-01:** Alcance mínimo — agregar los `ExpenseRecord`s de mercado privado usando `source_voucher_id` contra `Payment` (el patrón correcto y ya usado por el servicio hermano `AccountsReceivable::openItems()`). **No tocar** la lógica existente de `BudgetObligation`/`payment_order_id` en `AccountsPayable::openItems()`, aunque el scouting de esta discusión confirmó por grep que `payment_order_id` nunca se setea en ningún servicio del código — probablemente el "pagado" ya muestra 0 siempre para obligaciones públicas. Ese hallazgo es un bug pre-existente del MVP base (no introducido por esta milestone), documentado y registrado como **GitHub Issue #3** (`korozcolt/contpass`), explícitamente fuera de alcance de esta fase para no arriesgar el reporte que usa hoy el cliente público real (Aguas de Sucre S.A. E.S.P.) sin verificación de regresión dedicada.

### Columna "Obligación" para filas de mercado privado (D-02)
- **D-02:** Usar el número de comprobante/voucher (`expenseRecord.voucher.number`) en la columna "Obligación" para filas sin `BudgetObligation`. Mismo patrón que usa `AccountsReceivable` para su columna análoga "Comprobante" (`income.voucher.number`). Todo `ExpenseRecord` tiene `voucher_id`, así que siempre hay un valor que mostrar.

### Valor reportado — neto de retención (D-03)
- **D-03:** Para filas de mercado privado, la columna "Valor"/pendiente usa el monto **neto de retención**: `expenseRecord.amount - expenseRecord.withholding_amount`, no el bruto. Coincide con lo que `PostExpenseVoucher` efectivamente acredita a `payable_account_id` (lo realmente adeudado al tercero después de ReteICA/ReteFuente/ReteIVA). El bruto sobrestimaría lo debido. Nota: esto es un criterio distinto al que usa hoy `BudgetObligation.amount` en las filas públicas (bruto, sin restar nada) — es una diferencia intencional entre los dos tipos de fila, no una inconsistencia a resolver en esta fase.

### Claude's Discretion
- Estructura interna exacta de `AccountsPayable::openItems()` para combinar ambas fuentes (dos queries + `merge()`/`concat()` de Collections, vs. un método privado nuevo `openPrivateMarketItems()` que se fusiona con el resultado existente). El success criteria de ROADMAP.md pide "el mismo origen de datos... sin lógica de consulta duplicada entre pantalla/CSV/Excel" — no prescribe la forma interna del servicio.
- Nombre exacto de variables/métodos privados nuevos dentro de `AccountsPayable`.
- Si el "bucket" de antigüedad (`Corriente`/`31-60 días`/etc.) se calcula con el mismo método `bucket()` ya existente reusado tal cual, o uno idéntico duplicado — debe reusarse el mismo método privado, no duplicar la lógica de buckets.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Gap de origen (audit v1.0)
- `.planning/v1.0-MILESTONE-AUDIT.md` — gap `accounts-payable-private-market-scope` (bajo `gaps.flows`), afecta `XLSEXPORT-01`, `XLSEXPORT-03`. Describe el gap exacto que esta fase cierra.
- `.planning/ROADMAP.md` §"Phase 7: Cuentas por pagar — alcance mercado privado" — goal, success criteria, requirement `AP-01`.
- `.planning/REQUIREMENTS.md` §"Gap Closure Requirements (post v1.0 audit)" — `AP-01` texto completo.

### Servicio a modificar y su patrón de referencia
- `app/Services/Accounting/AccountsPayable.php` — servicio a extender (`openItems()`, actualmente 100% `BudgetObligation`-rooted vía `payment_order_id`).
- `app/Services/Accounting/AccountsReceivable.php` — servicio hermano ya correcto, mismo shape de array de salida (`third_party`, `accrual_date`, `amount`, `paid`, `pending`, `days_overdue`, `bucket`). Usar como plantilla del patrón `source_voucher_id` (D-01) y de la columna "voucher_number" (D-02).

### Punto de consumo (Phase 5 — no duplicar lógica)
- `app/Http/Controllers/AccountingReportController.php` — `accountsPayableRows()` (línea ~325), `accountsPayable()` CSV (línea ~212), `accountsPayableXlsx()` Excel (línea ~220). Todos tres consumen `AccountsPayable::openItems()` — solo cambia lo que ese método retorna, no estos tres métodos.
- `app/Filament/Pages/AccountsPayableReport.php` — página Filament que muestra el reporte en pantalla; también consume el mismo servicio, debe reflejar las nuevas filas automáticamente sin cambios si el shape de array se mantiene igual.

### Modelos y flujo de creación de ExpenseRecord
- `app/Models/ExpenseRecord.php` — `voucher_id`, `budget_obligation_id` (nullable), `amount`, `withholding_amount`, `support_number`, `accrual_date`, relación `voucher()` y `budgetObligation()`. No tiene relación directa a `ThirdParty` — se accede vía `voucher.thirdParty`.
- `app/Models/Voucher.php` — relación `thirdParty()` (línea 49), usada por ambos servicios hermanos.
- `app/Models/Payment.php` — `source_voucher_id` (el campo correcto para vincular pagos), `payment_order_id` (el campo que nunca se setea, ver Issue #3).
- `app/Services/Accounting/RegisterPayment.php` — único servicio que crea `Payment`; confirma que solo setea `source_voucher_id`, nunca `payment_order_id`.
- `app/Services/Accounting/PostExpenseVoucher.php` (línea 60-75) — confirma que `ExpenseRecord.amount` es bruto y que `payable_account_id` recibe el neto (`amount - withholding_amount`) — fuente de la decisión D-03.
- `app/Filament/Resources/ExpenseRecords/Pages/CreateExpenseRecord.php` — confirma que el flujo Filament de creación de gasto privado llama `PostExpenseVoucher::handle()` sin `$obligation`, dejando `budget_obligation_id` null (la causa raíz del gap original).

### Bug relacionado, fuera de alcance
- GitHub Issue #3 (`korozcolt/contpass`) — `payment_order_id` nunca se setea; "pagado" probablemente 0 siempre para obligaciones públicas. Documentado, no se arregla en esta fase (D-01).

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AccountsReceivable::openItems()` — plantilla directa para el nuevo camino de mercado privado: mismo shape de array, mismo uso de `source_voucher_id` contra `Payment`, mismo helper `bucket()` (duplicado en ambos servicios hoy — podría considerarse extraer a un trait/helper compartido, pero eso sería scope creep no pedido; mantener el patrón actual de duplicación entre servicios hermanos).
- `AccountingReportController::accountsPayableRows()` — no requiere cambios, solo el retorno de `AccountsPayable::openItems()` cambia de shape (más filas, mismos keys).

### Established Patterns
- Todo `ExpenseRecord` tiene `voucher_id` NOT NULL — no hay caso de `ExpenseRecord` sin voucher que manejar.
- El filtro `->filter(fn ($row) => $row['pending'] > 0.01)` ya existente en ambos servicios hermanos debe seguir aplicando también a las filas de mercado privado combinadas.

### Integration Points
- `AccountsPayable::openItems(Company $company): Collection` — única función pública a modificar; su firma y tipo de retorno no cambian.
- Ningún cambio requerido en `AccountingReportController`, rutas, ni páginas Filament — todo el fix vive en el servicio de dominio, consistente con la regla de arquitectura del proyecto ("la lógica contable vive en servicios de dominio").

</code_context>

<specifics>
## Specific Ideas

- Ninguna referencia específica adicional más allá de las decisiones capturadas — el audit y el scouting de código ya dejaron el problema y la solución claros.

</specifics>

<deferred>
## Deferred Ideas

- **Unificar el cálculo de "pagado" para TODAS las obligaciones (públicas y privadas) vía `source_voucher_id`:** considerado como Opción B en D-01 y descartado para esta fase — cambiaría comportamiento del reporte usado hoy por el cliente público real (Aguas de Sucre) sin verificación de regresión dedicada. Documentado como GitHub Issue #3 para una fase futura.
- **Extraer el método `bucket()` duplicado entre `AccountsPayable` y `AccountsReceivable` a un helper/trait compartido:** notado durante el scouting, no pedido por el usuario — scope creep, no se toca en esta fase.

### Reviewed Todos (not folded)
None — no había todos pendientes en el momento de esta discusión (`gsd-tools todo match-phase 07` devolvió 0 coincidencias).

</deferred>

---

*Phase: 07-cuentas-por-pagar-mercado-privado*
*Context gathered: 2026-09-18*

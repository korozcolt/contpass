# Phase 3: Conciliación bancaria CSV (Fase B) - Research

**Researched:** 2026-09-17
**Domain:** CSV import/parsing (encoding + delimiter detection), Eloquent schema design for a two-stage import→match→confirm workflow, bounded subset-sum matching, Filament v5 custom pages
**Confidence:** MEDIUM-HIGH (code-level findings HIGH — verified against installed vendor source; real-world Colombian bank CSV format assumptions LOW — no sample files available, flagged as open question)

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Formato del extracto CSV**
- **D-01:** Perfiles por banco hardcoded en código (enum/config), no una tabla configurable en base de datos. Sin UI de gestión de perfiles — evita que esto se vuelva su propia mini-feature de administración.
- **D-02:** Alcance inicial: 2-3 bancos (Bancolombia + Davivienda, los más comunes según el research de mercado del milestone), seleccionables en un dropdown al subir el extracto.
- **D-03:** El CSV debe tener encabezados de columna obligatorios. El importador auto-detecta el delimitador (`,` o `;`, ambos comunes en extractos colombianos/Excel-es). Si faltan encabezados, se rechaza el archivo completo con mensaje claro.

**Detección de reimportación (BANKREC-03)**
- **D-04:** El "período" se define por CashAccount + rango de fechas del propio archivo (fecha mínima/máxima de las filas). No se usa hash de archivo ni comparación fila por fila.
- **D-05:** Cualquier solape de fechas con un import previo para esa misma CashAccount rechaza el archivo COMPLETO — sin lógica de merge parcial ni importación de "solo filas nuevas". El usuario debe recortar su extracto para cubrir solo fechas nuevas antes de volver a subir.

**Motor de cruce (BANKREC-04)**
- **D-06:** Cruce 1:1 usa: monto exacto (sin tolerancia numérica), ventana de fecha configurable (± N días), y referencia opcional. Monto+fecha+referencia = "alta confianza"; monto+fecha sin referencia = "candidato" — ambos requieren confirmación manual (ninguno se auto-confirma, por BANKREC-05).
- **D-07:** Transferencias por lote: el sistema busca combinaciones automáticamente entre los `Payment` sin conciliar de esa `CashAccount` dentro de la misma ventana de fecha, y presenta la combinación como sugerencia agrupada para confirmación con un clic (o descarte).
- **D-08:** La búsqueda de combinaciones se limita a máximo 5 `Payment` candidatos por grupo (2^5 = 32 combinaciones máx. por línea del extracto).

**Flujo de revisión y confirmación (BANKREC-05)**
- **D-09:** Página Filament dedicada por import (patrón custom page, como los reportes existentes en `app/Filament/Pages/`), no un Resource CRUD estándar con modal por fila.
- **D-10:** Al confirmar un cruce (simple o de lote), se marca `Payment.reconciled_at` de cada `Payment` involucrado — reusa `is_reconciled` accessor/mutator existente, no se crea un campo nuevo de estado.
- **D-11:** Líneas del extracto sin ningún candidato quedan visibles como "pendientes" indefinidamente tras la revisión — no bloquean el resto del import.

### Claude's Discretion
- Estructura exacta de las nuevas tablas/modelos (`BankStatementImport`, `BankStatementLine` o nombres equivalentes).
- Mecanismo exacto de auto-detección de delimitador CSV (heurística de conteo de caracteres vs. librería).
- Tamaño exacto de la ventana de fecha por defecto (± N días) — dentro del rango "días, no semanas".

### Deferred Ideas (OUT OF SCOPE)
- **BANKREC-07 (bulk-confirm):** confirmación masiva de un lote de cruces — v2, no discutido en profundidad.
- **Catálogo configurable de perfiles de banco (UI de administración):** descartado por ahora — si se necesitan más de 2-3 bancos o gestión sin tocar código, se convierte en fase/feature propia.
- **Importación con solo filas nuevas en solapes parciales:** descartado por complejidad/riesgo de falsos "duplicados nuevos".
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| BANKREC-01 | Subir extracto CSV asociado a `CashAccount` | FileUpload en Filament Page/Action + disco `local` privado (ya configurado como default); esquema `bank_statement_imports.cash_account_id` |
| BANKREC-02 | Normalizar codificación (Windows-1252/UTF-8) y reconocer formatos de fecha antes de parsear | `mb_check_encoding()` + `League\Csv\CharsetConverter` (idéntico al mecanismo interno de `Filament\Actions\ImportAction::detectCsvEncoding()`, verificado en vendor); reusar patrón `CarbonImmutable::hasFormat()` ya existente en `ArchiveMasterPreviewImporter::parseDate()` |
| BANKREC-03 | Reimportar período ya importado se rechaza explícitamente, nunca se duplica en silencio | Cálculo de `starts_on`/`ends_on` desde el archivo + query de solape contra `bank_statement_imports` de la misma `cash_account_id` antes de crear cualquier línea (transacción atómica, todo o nada) |
| BANKREC-04 | Proponer cruces 1:1 y por lote (suma de varios `Payment`) | Servicio de dominio nuevo (`app/Services/Accounting/`) con matcher exacto por monto+ventana de fecha+referencia opcional, y matcher de subconjuntos acotado para el caso de lote (ver sección "Motor de cruce por lote") |
| BANKREC-05 | Confirmación explícita por cruce, ninguna automática | Página Filament dedicada (D-09) con acción "Confirmar" por fila/grupo que llama al servicio de confirmación, nunca se ejecuta en el import mismo |
| BANKREC-06 | Filas no parseables se muestran como rechazadas explícitas, no se omiten | Patrón `rejected: array<{reason}>` ya usado en `ArchiveMasterPreviewImporter`, extendido a nivel de fila individual dentro de `bank_statement_lines` (status `rejected` + `reject_reason`) |
</phase_requirements>

## Summary

Esta fase no requiere ninguna dependencia nueva de Composer/NPM: `league/csv` 9.28.0 ya está vendorizado (vía `filament/actions`, dependencia transitiva de `filament/filament` v5.6) e incluye exactamente las dos utilidades que BANKREC-02/03 necesitan — detección de delimitador (`League\Csv\Info::getDelimiterStats()`) y conversión de charset (`League\Csv\CharsetConverter`). Verifiqué el código fuente instalado de `Filament\Actions\ImportAction` (que ya se vendoriza pero no se usa hoy en el proyecto) y confirmé que internamente usa exactamente estas dos utilidades para lograr auto-detección de delimitador y encoding — es la prueba de que esta combinación es el patrón "oficial" del ecosistema Filament, no una heurística de cosecha propia.

**Hallazgo importante:** Filament también ofrece un componente completo `ImportAction`/`Importer` (mapeo de columnas CSV a un modelo Eloquent, con jobs en cola, tabla `failed_import_rows`, notificaciones). Lo evalué en profundidad pero **no lo recomiendo para esta fase**: su UI siempre muestra un "column mapper" genérico al usuario en cada import, lo cual contradice directamente D-01 (perfiles de banco hardcoded, sin UI de gestión/mapeo visible al usuario); además requiere publicar y correr 3 migraciones nuevas del paquete (`imports`, `exports`, `failed_import_rows` — no se auto-registran, `runsMigrations` está en `false` por defecto en el proveedor) y su ejecución es asíncrona por lotes en cola (`Bus::batch`), lo que complica el requisito de calcular candidatos de cruce inmediatamente después del import. La recomendación es usar las utilidades de bajo nivel de `league/csv` directamente dentro de un servicio de dominio propio, siguiendo el patrón transaccional + fila-rechazada-explícita que ya existe en `ArchiveMasterPreviewImporter`.

El motor de cruce por lote (D-07/D-08) es el punto de mayor riesgo de diseño: la instrucción del usuario ("2^5 = 32 combinaciones máx.") asume implícitamente que el *pool* de candidatos ya está acotado a 5 antes de generar combinaciones, pero el pool real (pagos sin conciliar de una `CashAccount` dentro de la ventana de fecha) puede tener más de 5 elementos. Este research detalla el algoritmo correcto: acotar el pool candidato por ventana de fecha + un límite superior configurable, y generar solo combinaciones de tamaño 2 a 5 (no el powerset completo) sobre ese pool acotado — ver sección dedicada.

No existe en el código actual ningún precedente de `FileUpload` en un formulario Filament, ni de relaciones `belongsToMany`/pivot. Esta fase introduce ambos patrones por primera vez en el proyecto — no hay convención existente que seguir, así que las decisiones de este research (esquema, disco de almacenamiento) se convierten en el precedente para futuras fases.

**Primary recommendation:** Construir un servicio de dominio `ImportBankStatement` (usando `league/csv` directamente, no `Filament\Actions\ImportAction`) + un esquema de 3 tablas (`bank_statement_imports`, `bank_statement_lines`, `bank_statement_matches` con pivot a `payments`) + una página Filament custom de revisión, todo siguiendo los patrones arquitectónicos ya establecidos (servicios `handle()`, páginas `Page implements HasTable`).

## Standard Stack

### Core (ya vendorizado, sin nueva dependencia)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `league/csv` | 9.28.0 (instalado, verificado con `composer show`) | Lectura CSV, detección de delimitador, conversión de encoding | Ya usado transitivamente por `filament/actions`; es la librería que el propio Filament usa internamente para estos problemas (ver `ImportAction::guessCsvDelimiter()` / `::detectCsvEncoding()` en `vendor/filament/actions/src/ImportAction.php:580-494`) |
| `filament/filament` | 5.6.8 | `FileUpload` field para subir el CSV desde una página/acción custom | Ya en uso en el resto del panel |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `mb_check_encoding()` (ext-mbstring, PHP core) | PHP 8.4 built-in | Heurística de detección de encoding (UTF-8 válido vs. no) | Antes de decidir si aplicar conversión Windows-1252→UTF-8; mismo mecanismo que usa `Filament\Actions\ImportAction::detectCsvEncoding()` |
| `Carbon\CarbonImmutable::hasFormat()` | ya en uso (`nesbot/carbon`, dependencia de Laravel) | Validación estricta de fecha contra una lista de formatos candidatos | Ya usado en `ArchiveMasterPreviewImporter::parseDate()` — extender la lista de formatos, no reinventar el mecanismo |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Servicio de dominio a medida sobre `league/csv` | `Filament\Actions\ImportAction` + `Importer` (ya vendorizado, cero instalación) | Machinery completa de column-mapping visible al usuario (contradice D-01), requiere publicar+correr 3 migraciones nuevas del paquete, procesamiento async por `Bus::batch` (complica calcular candidatos de cruce justo después del import). Válido si en el futuro se necesita soporte de N bancos configurables por el usuario — hoy no es el caso. |
| Un solo modelo `BankStatementMatch` con pivot `payments` para 1:1 y lote | Dos tablas separadas (`bank_statement_line_payment` directo para 1:1, tabla de grupo aparte para lotes) | La unificación en un solo modelo con pivot evita lógica duplicada de confirmación/descarte entre los dos casos (D-06 y D-07 comparten el mismo flujo de confirmación en D-10) |
| Búsqueda de subconjuntos acotada por pool + tamaño de grupo (2..5) | Powerset completo (2^n) sobre todos los pagos sin conciliar de la cuenta | El powerset completo explota combinatoriamente fuera de pools pequeños; ver sección "Motor de cruce por lote" |

**Instalación:** Ninguna — todo lo necesario ya está en `composer.lock`. No se requiere `composer require`.

**Version verification:**
```bash
composer show league/csv   # 9.28.0, released 2025-12-27
composer show filament/actions  # v5.6.8, released 2026-07-02
```
Ambos confirmados directamente contra el `vendor/` instalado en este checkout (no solo `composer.json`), por lo tanto HIGH confidence.

## Architecture Patterns

### Recommended Project Structure
```
app/
├── Enums/
│   └── BankProfile.php                    # Bancolombia | Davivienda (D-01/D-02, HasLabel)
├── Models/
│   ├── BankStatementImport.php             # company_id, cash_account_id, bank profile, starts_on, ends_on
│   ├── BankStatementLine.php               # belongsTo import; status pending|matched|rejected
│   └── BankStatementMatch.php              # belongsTo line; belongsToMany Payment (pivot); confidence, status
├── Services/
│   └── Accounting/
│       ├── ImportBankStatement.php         # handle(): parsea, detecta encoding/delimitador, valida solape, crea líneas
│       ├── ProposeBankStatementMatches.php # handle(): genera BankStatementMatch propuestos (1:1 y lote) para un import
│       └── ConfirmBankStatementMatch.php   # handle(): marca is_reconciled=true en cada Payment del match, marca línea como matched
└── Filament/
    └── Pages/
        └── BankStatementReview.php         # página dedicada por import (D-09), lista líneas + candidatos + botón confirmar
```

### Pattern 1: Importador con detección de delimitador/encoding vía `league/csv`
**What:** Leer el stream del archivo subido, detectar BOM/encoding con `mb_check_encoding()`, envolver el stream con `CharsetConverter` si es necesario, detectar delimitador con `Info::getDelimiterStats()`, y solo entonces crear el `Reader` con encabezados.
**When to use:** Al recibir el archivo en `ImportBankStatement::handle()`, antes de iterar filas.
**Example (mecanismo verificado en `vendor/filament/actions/src/ImportAction.php:417-494`):**
```php
// Adaptado del mecanismo interno de Filament\Actions\ImportAction (mismo enfoque, sin adoptar la clase completa)
use League\Csv\CharsetConverter;
use League\Csv\Info;
use League\Csv\Reader;

$resource = fopen($path, 'r');

$sample = fread($resource, 8192);
rewind($resource);

$inputEncoding = null;
foreach (['UTF-8', 'Windows-1252', 'ISO-8859-1'] as $candidate) {
    if (mb_check_encoding($sample, $candidate)) {
        $inputEncoding = $candidate;
        break;
    }
}

if ($inputEncoding !== null && $inputEncoding !== 'UTF-8') {
    CharsetConverter::register();
    stream_filter_append($resource, CharsetConverter::getFiltername($inputEncoding, 'UTF-8'), STREAM_FILTER_READ);
}

$csv = Reader::from($resource);
$csv->setHeaderOffset(null); // temporal para detectar delimitador sin encabezados aún
$stats = Info::getDelimiterStats($csv, [',', ';'], limit: 10);
$delimiter = array_search(max($stats), $stats);
$csv->setDelimiter($delimiter);
$csv->setHeaderOffset(0);

if ($csv->getHeader() === []) {
    throw ValidationException::withMessages(['file' => 'El archivo no tiene encabezados de columna.']);
}
```
**Note on "UTF-8 or Windows-1252" order:** probar UTF-8 primero es obligatorio — casi cualquier texto ASCII puro pasa como válido en Windows-1252 también, pero un texto con tildes en UTF-8 real (2 bytes por carácter) casi nunca es válido Windows-1252 por accidente. El orden importa.

### Pattern 2: Rechazo de fila individual sin abortar el import completo (BANKREC-06)
**What:** Igual que `ArchiveMasterPreviewImporter::import()`, envolver todo en una transacción, pero capturar el fallo de *parseo de fecha por fila* como una fila con `status = rejected` + `reject_reason`, no como excepción que aborta el import — a diferencia del solape de período (D-05), que sí aborta el archivo completo *antes* de crear cualquier línea.
**Example:**
```php
// Source: patrón adaptado de app/Services/Imports/ArchiveMasterPreviewImporter.php
foreach ($csv->getRecords() as $offset => $record) {
    try {
        $date = $this->parseDate($record[$profile->dateColumn()]); // reusa CarbonImmutable::hasFormat()
    } catch (ValidationException $e) {
        $import->lines()->create([
            'status' => 'rejected',
            'reject_reason' => $e->validator->errors()->first(),
            'raw_row' => $record,
        ]);
        continue; // BANKREC-06: visible, no silencioso — pero no aborta el resto del archivo
    }
    // ... crear línea válida
}
```

### Pattern 3: Página Filament de revisión (D-09), siguiendo el patrón `BankReconciliationReport`
**What:** `Page implements HasTable` con `InteractsWithTable`, tabla alimentada por un array/`Collection` computado en memoria (no una query directa a `bank_statement_lines`, porque cada fila necesita anexar sus candidatos de match calculados por el servicio), acción por fila para confirmar.
**When to use:** Ruta dedicada por import (`?import={id}` como filtro o parámetro de página), no un Resource.
**Reference:** `app/Filament/Pages/BankReconciliationReport.php` — mismo patrón `records(fn (...) => LengthAwarePaginator)`, filtros `AboveContent`, `headerActions`.

### Anti-Patterns to Avoid
- **Confiar en el MIME type del navegador para validar CSV:** el propio código de Filament acepta 7 MIME types distintos para CSV (`text/csv`, `text/plain`, `application/vnd.ms-excel`, etc. — ver `ImportAction::setUp()` línea 93) porque los navegadores son inconsistentes. Validar por extensión (`extensions:csv,txt`) + intentar parsear, no por MIME type estricto.
- **Comparar montos con `==` en float:** usar centavos enteros (`(int) round($amount * 100)`) para la comparación "monto exacto" de D-06 — comparación de floats con igualdad exacta es un bug clásico de precisión de punto flotante.
- **Generar el powerset completo (2^n) sobre todos los pagos sin conciliar de la cuenta:** ver sección dedicada abajo — el pool debe acotarse antes de combinar.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Detección de delimitador CSV (`,` vs `;`) | Heurística propia de conteo de caracteres | `League\Csv\Info::getDelimiterStats($reader, [',', ';'], limit: 10)` | Ya vendorizado, es exactamente lo que usa `Filament\Actions\ImportAction` internamente (verificado en `vendor/filament/actions/src/ImportAction.php:591`) |
| Conversión Windows-1252 → UTF-8 | `iconv()`/`mb_convert_encoding()` manual sobre el contenido completo en memoria | `League\Csv\CharsetConverter` como stream filter (`CharsetConverter::addTo()` o `stream_filter_append` + `CharsetConverter::getFiltername()`) | Procesa el archivo como stream (no carga todo en memoria), maneja BOM correctamente, y es el mecanismo que el propio Filament usa (`ImportAction::getUploadedFileStream()`) |
| Validación estricta de fecha con múltiples formatos candidatos | Regex a mano por formato | `CarbonImmutable::hasFormat($string, $format)` antes de `createFromFormat()` | Ya es el patrón exacto en `ArchiveMasterPreviewImporter::parseDate()` — evita el problema de que `createFromFormat` de Carbon/DateTime hace parseo "lenient" (ej. `31/02/2026` se reinterpreta silenciosamente como una fecha de marzo en vez de fallar) |
| Marcar un `Payment` como conciliado | Escribir `reconciled_at` directamente | `$payment->update(['is_reconciled' => true])` | El accessor/mutator virtual ya existe en `app/Models/Payment.php:42-47` y es explícitamente el mecanismo que D-10 exige reusar |

**Key insight:** Todo el trabajo "difícil" de parseo de CSV (delimitador, encoding, BOM) ya fue resuelto por `league/csv` y ya está pagado (vendorizado) en este proyecto — el único código nuevo genuinamente necesario es la lógica de negocio específica de ContPass (perfiles de banco, detección de solape de período, motor de cruce, confirmación).

## Motor de cruce por lote (BANKREC-04, D-07/D-08) — diseño explícito

Este es el punto flagged como riesgo en `.planning/STATE.md` ("matching many-to-one... necesita diseño explícito durante plan-phase"). La instrucción del usuario dice "2^5 = 32 combinaciones máx." pero esto solo es válido *si el pool de candidatos ya tiene como máximo 5 elementos*. En la práctica, el pool de pagos sin conciliar de una `CashAccount` dentro de una ventana de fecha puede tener más de 5 elementos (ej. una empresa con muchos pagos pequeños en la misma semana).

**Algoritmo recomendado (dos pasos, no un solo powerset):**

1. **Acotar el pool candidato** para una línea de extracto con monto `M` y fecha `D`:
   - Filtrar `Payment` de la misma `CashAccount`, sin conciliar (`is_reconciled = false`), con `paid_on` entre `D - N` y `D + N` días.
   - Excluir pagos ya usados en un `BankStatementMatch` con status `confirmed` para otra línea.
   - Si el pool resultante excede un límite superior configurable (recomendado: 10-12), **no intentar combinaciones de lote** para esa línea — solo ofrecer el matcher 1:1 normal. Registrar esto como "sin sugerencia de lote (pool > límite)" en vez de fallar o colgar el request.

2. **Generar combinaciones de tamaño 2 a 5** (no tamaño 1 — eso ya lo cubre el matcher 1:1 de D-06, y no el powerset completo 2^n) sobre el pool acotado, comparando la suma de montos en centavos enteros contra el monto de la línea:
   - Para un pool de tamaño `n` (n ≤ 12), el total de combinaciones a evaluar es `C(n,2) + C(n,3) + C(n,4) + C(n,5)` — para n=12 esto es 66+220+495+792 = 1573 combinaciones, trivial de calcular por línea en PHP puro sin librería adicional.
   - PHP no tiene una función nativa de combinaciones; usar un generador recursivo simple (no hay necesidad de una librería — es ~15 líneas de código, y forma parte de la lógica de negocio propia del dominio, no un problema genérico resuelto en el ecosistema).

**Dónde vive esta lógica:** un servicio de dominio dedicado (`ProposeBankStatementMatches`), no en la página Filament ni en el modelo — sigue la convención ya establecida (`app/Services/Accounting/*Rules*`, servicios de una sola responsabilidad).

**Advertencia explícita para el planner:** si el volumen real de pagos sin conciliar por `CashAccount` es alto (ej. cientos de pagos pequeños recurrentes en la misma ventana de días), el límite superior del pool (paso 1) es la salvaguarda real contra explosión combinatoria — el "2^5 = 32" del usuario describe el costo de evaluar *un* pool ya acotado a 5, no una garantía de que el pool siempre tendrá 5 elementos o menos. Este research recomienda tratar "máximo 5 payments por combinación" (D-08) como el tamaño máximo de *grupo sugerido*, y el límite de pool (10-12) como un parámetro adicional de diseño a confirmar explícitamente en plan-phase — no estaba en la discusión original con el usuario.

## Esquema de datos recomendado

```
bank_statement_imports
├── id
├── company_id          → companies
├── cash_account_id      → cash_accounts (BANKREC-01)
├── bank                 (string, enum BankProfile: 'bancolombia' | 'davivienda')
├── file_name            (string, nombre original del archivo)
├── starts_on            (date, mínimo de la columna fecha del archivo — D-04)
├── ends_on              (date, máximo de la columna fecha del archivo — D-04)
├── imported_by          → users, nullable
└── timestamps
    (índice: [cash_account_id, starts_on, ends_on] para la query de solape de D-05)

bank_statement_lines
├── id
├── bank_statement_import_id → bank_statement_imports, cascadeOnDelete
├── line_date             (date, nullable si status=rejected por fecha ilegible)
├── description           (string, concepto/glosa original del banco)
├── amount                (decimal 15,2 — signo: positivo=abono/crédito, negativo=cargo/débito, a confirmar contra ejemplos reales)
├── reference             (string, nullable — número de cheque/transferencia si el banco lo expone)
├── raw_row               (json — fila cruda original, para auditoría/debug, igual que el espíritu de trazabilidad del Core Value)
├── status                (string, enum: pending | matched | rejected — BANKREC-06/D-11)
├── reject_reason         (text, nullable)
└── timestamps

bank_statement_matches
├── id
├── bank_statement_line_id → bank_statement_lines, cascadeOnDelete
├── confidence             (string, enum: high | candidate — D-06)
├── status                 (string, enum: proposed | confirmed | discarded)
├── confirmed_by           → users, nullable
├── confirmed_at           (timestamp, nullable)
└── timestamps

bank_statement_match_payment (pivot, sin modelo propio)
├── bank_statement_match_id → bank_statement_matches, cascadeOnDelete
└── payment_id              → payments, cascadeOnDelete
    (unifica el caso 1:1 de D-06 [1 payment] y el caso de lote de D-07 [2-5 payments] bajo el mismo modelo de confirmación)
```

**Justificación de unificar 1:1 y lote en `BankStatementMatch` + pivot:** D-10 exige el mismo flujo de confirmación para ambos casos ("al confirmar un cruce, simple o de lote, se marca `Payment.reconciled_at` de cada Payment involucrado"). Modelar ambos como "un match con 1..5 payments vía pivot" evita duplicar la lógica de confirmación/descarte entre dos tablas separadas.

**Nota de convención de Fase 1/2:** las migraciones recientes del proyecto (`2026_09_16_210230_create_quotations_table.php`) usan `$table->id()`, `foreignId(...)->constrained()->cascadeOnDelete()` (o `restrictOnDelete()` para catálogos que no deben perder integridad referencial), enums como columna `string` con default, e índices explícitos — el esquema arriba sigue exactamente ese patrón.

## Common Pitfalls

### Pitfall 1: Asumir que el navegador siempre envía un MIME type consistente para CSV
**What goes wrong:** El `<input type="file">` reporta MIME types distintos según el navegador/OS para el mismo archivo `.csv` (a veces `text/plain`, a veces `application/vnd.ms-excel` si Excel lo generó).
**Why it happens:** No hay un estándar MIME real para CSV.
**How to avoid:** Validar por extensión de archivo (`extensions:csv,txt`) y por la capacidad real de parsearlo (encabezados detectables), no por whitelist estricta de MIME type. Filament mismo acepta 7 variantes de MIME (`vendor/filament/actions/src/ImportAction.php:93`).
**Warning signs:** Usuarios reportando "no me deja subir el archivo" con un CSV válido.

### Pitfall 2: Probar Windows-1252 antes de UTF-8 al detectar encoding
**What goes wrong:** Casi cualquier byte stream es "válido" como Windows-1252 (es un charset de 1 byte que acepta casi cualquier valor 0-255), así que si se prueba primero, textos UTF-8 reales con tildes se "detectan" incorrectamente como Windows-1252 y se corrompen al forzar la conversión.
**Why it happens:** `mb_check_encoding()` es permisivo con charsets de 1 byte.
**How to avoid:** Probar SIEMPRE UTF-8 primero en la lista de candidatos (así lo hace `Filament\Actions\ImportAction::detectCsvEncoding()` — UTF-8 es el primer elemento del array `$encodings`).
**Warning signs:** Tildes/ñ mostrándose como caracteres corruptos (mojibake) en descripciones de línea del extracto.

### Pitfall 3: Parseo "lenient" de fechas con `createFromFormat`
**What goes wrong:** `DateTime`/`Carbon::createFromFormat('d/m/Y', '31/02/2026')` no lanza excepción — reinterpreta silenciosamente la fecha como si fuera válida (rollover a marzo), violando BANKREC-02 ("mostrar explícitamente como rechazada" cualquier fecha no reconocible).
**Why it happens:** Comportamiento histórico de PHP DateTime, heredado por Carbon.
**How to avoid:** Usar `CarbonImmutable::hasFormat($string, $format)` para validar ANTES de parsear — patrón ya existente y correcto en `ArchiveMasterPreviewImporter::parseDate()`.
**Warning signs:** Fechas de conciliación que no coinciden con el extracto original pero no aparecen como fila rechazada.

### Pitfall 4: Comparación de montos con float `==`
**What goes wrong:** "Monto exacto sin tolerancia" (D-06) implementado con `$a == $b` sobre floats falla intermitentemente por representación binaria imprecisa (ej. `0.1 + 0.2 !== 0.3` en IEEE 754).
**Why it happens:** PHP floats son IEEE 754 double; la aritmética decimal no es exacta en binario.
**How to avoid:** Comparar en centavos enteros: `(int) round($amount * 100) === (int) round($payment->amount * 100)`, o usar `bccomp()` si se prefiere mantener precisión decimal arbitraria.
**Warning signs:** Cruces que "deberían" calzar exacto pero el sistema no los propone.

### Pitfall 5: Explosión combinatoria del matcher de lote sin acotar el pool
**What goes wrong:** Generar el powerset completo (2^n) sobre TODOS los pagos sin conciliar de una `CashAccount` (no solo los de la ventana de fecha) puede ser cientos o miles de elementos — 2^100 es computacionalmente imposible.
**Why it happens:** Interpretar literalmente "2^5 = 32 combinaciones" (D-08) como el tamaño total del pool, en vez de como el tamaño máximo de un *grupo sugerido* dentro de un pool ya acotado.
**How to avoid:** Ver sección "Motor de cruce por lote" arriba — filtrar por ventana de fecha primero, y aplicar un límite superior explícito al tamaño del pool antes de generar combinaciones.
**Warning signs:** Timeout o alto uso de CPU al abrir la página de revisión de un import con muchos pagos pendientes.

## Code Examples

### Detección de solape de período (D-04/D-05) antes de crear cualquier línea
```php
// Todo o nada: se calcula ANTES de abrir la transacción de creación de líneas
$startsOn = $rows->min('date');
$endsOn = $rows->max('date');

$overlaps = BankStatementImport::query()
    ->where('cash_account_id', $cashAccount->id)
    ->where(fn ($query) => $query
        ->whereBetween('starts_on', [$startsOn, $endsOn])
        ->orWhereBetween('ends_on', [$startsOn, $endsOn])
        ->orWhere(fn ($query) => $query->where('starts_on', '<=', $startsOn)->where('ends_on', '>=', $endsOn)))
    ->exists();

if ($overlaps) {
    throw ValidationException::withMessages([
        'file' => "El rango {$startsOn}–{$endsOn} se solapa con un extracto ya importado para esta cuenta.",
    ]);
}
```

### Confirmación de un match (simple o de lote) — D-10
```php
// Source: patrón basado en app/Models/Payment.php:42-47 (accessor/mutator existente)
DB::transaction(function () use ($match) {
    foreach ($match->payments as $payment) {
        $payment->update(['is_reconciled' => true]); // dispara el mutator reconciled_at = now()
    }

    $match->update(['status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => auth()->id()]);
    $match->line->update(['status' => 'matched']);
});
```

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP ext-mbstring | Detección de encoding (`mb_check_encoding`) | ✓ (PHP 8.4.23 activo vía Herd) | — | — |
| `league/csv` | Delimitador/encoding/lectura CSV | ✓ | 9.28.0 | — |
| `filament/filament` | `FileUpload`, página custom | ✓ | 5.6.8 | — |
| Cola (queue worker) | NO requerido si se evita `Filament\Actions\ImportAction` (recomendación de este research); el servicio propio corre sincrónicamente en el request | N/A con la recomendación | `QUEUE_CONNECTION=database` en `.env` (confirmado) | Si en algún momento se decide usar `ImportAction`, sí se necesitaría un worker corriendo (`composer run dev` ya lo incluye) |

**Missing dependencies with no fallback:** Ninguno — todo lo necesario ya está instalado.

**Missing dependencies with fallback:** Ninguno relevante a la recomendación de este research (importador síncrono, sin `Filament\Actions\ImportAction`).

## Open Questions

1. **Formato real de columnas/encoding/fecha/separador decimal de los extractos CSV exportables de Bancolombia y Davivienda para empresas**
   - What we know: ambos bancos ofrecen descarga de extractos vía sus portales empresariales (Sucursal Virtual Negocios para Bancolombia, Portal Transaccional/App Empresas para Davivienda); la búsqueda web no encontró especificaciones públicas de columnas exactas, encoding, o separador decimal — esa información está detrás de login y no se documenta públicamente.
   - What's unclear: si el separador decimal es punto o coma, si el monto viene en una sola columna con signo o en columnas separadas débito/crédito, el nombre exacto de las columnas de fecha/referencia, y si el encoding real de exportación es Windows-1252 (típico de exports generados por sistemas legacy/COBOL bancarios colombianos) o UTF-8 (típico si el export es reciente/vía API web).
   - Recommendation: **antes o durante Wave 0 de esta fase**, obtener 1-2 archivos CSV reales de extracto de cada banco (el usuario puede descargarlos de su propia cuenta empresarial) para validar contra datos reales el diseño del `BankProfile` enum (columnas, formato de fecha, separador decimal, encoding real). Diseñar el importador para que sea trivial ajustar estos parámetros por perfil sin reescribir el motor de detección de encoding/delimitador (que sí es genérico y no depende del banco).

2. **Signo del monto en el extracto: ¿positivo/negativo en una columna, o dos columnas débito/crédito separadas?**
   - What we know: el patrón de reportes existentes de ContPass (`BankReconciliation::payments()`) usa `signed_amount` (positivo=débito contable/entrada de banco, negativo=crédito contable/salida) como convención interna.
   - What's unclear: cómo lo expone cada banco en su CSV — sin muestra real no se puede confirmar.
   - Recommendation: el `BankProfile` enum debe encapsular esta normalización (columna(s) de origen → `amount` con signo consistente interno), como parte de la lógica específica de cada perfil, no como una decisión genérica del importador.

3. **Tamaño exacto de la ventana de fecha por defecto (± N días) y del límite superior del pool de candidatos de lote**
   - What we know: el usuario dejó esto a discreción, dentro de "días, no semanas" para la ventana; el límite de pool para el matcher de lote (10-12 sugerido en este research) no fue discutido explícitamente con el usuario en absoluto.
   - What's unclear: valores exactos óptimos sin datos reales de volumen de `Payment` por `CashAccount`.
   - Recommendation: hacer ambos valores configurables (constante en el servicio o `config('contpass.php')`, no hardcoded en múltiples lugares), con defaults razonables (ventana: 3-5 días; pool máximo: 10-12) documentados explícitamente en el plan para que el usuario los pueda ajustar sin re-planificar la fase.

## Sources

### Primary (HIGH confidence — verificado contra código instalado en este proyecto)
- `vendor/league/csv/src/functions.php`, `vendor/league/csv/src/Info.php`, `vendor/league/csv/src/CharsetConverter.php` — API real de detección de delimitador (`Info::getDelimiterStats()`) y conversión de charset, versión 9.28.0 instalada.
- `vendor/filament/actions/src/ImportAction.php` (líneas 89-181, 417-494, 580-594) — mecanismo interno completo de detección de encoding y delimitador que Filament usa en producción; base de la recomendación de "don't hand-roll".
- `vendor/spatie/laravel-package-tools/src/Concerns/PackageServiceProvider/ProcessMigrations.php` y `vendor/filament/actions/src/ActionsServiceProvider.php` — confirmación de que las migraciones de `imports`/`exports`/`failed_import_rows` NO se auto-registran (`runsMigrations` default `false`), solo se publican bajo el tag `filament-actions-migrations`.
- `app/Services/Imports/ArchiveMasterPreviewImporter.php`, `app/Services/Accounting/BankReconciliation.php`, `app/Models/Payment.php`, `app/Filament/Resources/Payments/Tables/PaymentsTable.php`, `app/Filament/Pages/BankReconciliationReport.php`, `app/Filament/Pages/LedgerReport.php` — patrones de código existentes a reusar/seguir.
- `database/migrations/2026_09_16_210230_create_quotations_table.php` — convención de migración reciente a seguir.
- `composer show league/csv`, `composer show filament/actions` — versiones exactas instaladas.
- `php artisan migrate:status`, `config/filesystems.php`, `.env` (`QUEUE_CONNECTION=database`) — estado real del entorno de este checkout.

### Secondary (MEDIUM confidence)
- [League\Csv CharsetConverter docs](https://csv.thephpleague.com/9.0/converter/charset/) — confirma que no hay auto-detección de encoding en la librería (solo BOM), consistente con lo verificado en el código fuente.
- [Filament FileUpload docs (5.x)](https://filamentphp.com/docs/5.x/forms/file-upload) — comportamiento general de `FileUpload` en formularios/acciones custom.

### Tertiary (LOW confidence — sin verificación oficial, marcado explícitamente en Open Questions)
- Búsquedas web sobre formato de extractos CSV de Bancolombia/Davivienda para empresas — no se encontró documentación pública de columnas/encoding/formato de fecha exactos; requiere muestra real de archivo antes de finalizar el diseño de `BankProfile`.

## Metadata

**Confidence breakdown:**
- Standard stack (league/csv, Filament FileUpload): HIGH — verificado directamente contra `vendor/` instalado, no solo documentación.
- Don't Hand-Roll / patrones de código reusable: HIGH — verificado contra archivos reales del proyecto.
- Motor de cruce por lote (algoritmo): MEDIUM — diseño propio razonado a partir de las restricciones dadas (D-07/D-08), no hay una librería/patrón estándar de terceros para "subset-sum acotado" en el ecosistema Laravel; es lógica de dominio genuina.
- Formato real de extractos bancarios colombianos (Bancolombia/Davivienda): LOW — sin acceso a archivos reales, flagged explícitamente como Open Question #1 y #2.

**Research date:** 2026-09-17
**Valid until:** ~30 días (dependencias estables; el riesgo real de caducidad está en Open Question #1, que depende de que el usuario provea archivos de muestra, no del paso del tiempo)

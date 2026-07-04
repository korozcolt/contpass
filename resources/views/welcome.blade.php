<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mapa estructural de ContPass: módulos, arquitectura, servicios de dominio, flujos contables y decisiones técnicas del sistema.">
    <link rel="icon" href="{{ asset('images/brand/contpass-icon-32.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/contpass-icon-180.png') }}">

    <title>ContPass | Arquitectura del sistema</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-950 font-[var(--font-display)] text-white antialiased">
    <header class="sticky top-0 z-20 border-b border-white/10 bg-zinc-950/95">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 lg:px-8">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3" aria-label="ContPass arquitectura">
                <span class="flex h-11 w-36 items-center rounded-md border border-white/15 bg-white px-3 shadow-sm sm:w-44">
                    <img src="{{ asset('images/brand/contpass-logo-horizontal.png') }}" alt="ContPass" class="h-8 w-full object-contain">
                </span>
                <span class="hidden text-xs font-semibold text-zinc-400 md:block">Mapa estructural del software</span>
            </a>

            <nav class="hidden items-center gap-5 text-sm font-semibold text-zinc-300 lg:flex" aria-label="Navegación de arquitectura">
                <a class="hover:text-amber-200" href="#capas">Capas</a>
                <a class="hover:text-amber-200" href="#servicios">Servicios</a>
                <a class="hover:text-amber-200" href="#flujos">Flujos</a>
                <a class="hover:text-amber-200" href="#garantias">Garantías</a>
            </nav>

            <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white px-4 py-2 text-sm font-bold text-zinc-950 transition hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-zinc-950">
                Abrir panel
            </a>
        </div>
    </header>

    <main>
        <section class="border-b border-white/10">
            <div class="mx-auto grid min-h-[calc(100vh-150px)] max-w-7xl items-center gap-10 px-5 py-12 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
                <div>
                    <div class="inline-flex items-center gap-3 rounded-md border border-sky-300/40 bg-sky-300/10 px-3 py-2">
                        <img src="{{ asset('images/brand/contpass-logo-mark.png') }}" alt="" class="size-8 rounded-sm bg-white object-contain p-1">
                        <span class="text-sm font-bold text-sky-200">Blueprint interactivo</span>
                    </div>
                    <h1 class="mt-6 max-w-3xl text-5xl font-black leading-none text-white md:text-7xl">
                        Arquitectura contable antes que pantalla bonita.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-300">
                        Esta página explica cómo está pensado ContPass: dominio, validaciones, servicios, reportes y límites del MVP. No expone datos operativos, clientes, proveedores, valores reales ni información sensible.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="#capas" class="inline-flex items-center justify-center rounded-md bg-amber-300 px-5 py-3 text-sm font-black text-zinc-950 transition hover:bg-amber-200">
                            Explorar arquitectura
                        </a>
                        <a href="#flujos" class="inline-flex items-center justify-center rounded-md border border-white/15 px-5 py-3 text-sm font-bold text-white transition hover:border-sky-300 hover:text-sky-200">
                            Ver flujos críticos
                        </a>
                    </div>
                </div>

                <section class="hidden lg:block" aria-label="Diagrama estructural de ContPass">
                    <div class="rounded-lg border border-white/10 bg-white p-4 text-zinc-950 shadow-2xl">
                        <div class="flex items-center justify-between border-b border-zinc-200 pb-4">
                            <div>
                                <p class="text-sm font-black uppercase text-zinc-500">ContPass Core</p>
                                <p class="mt-1 text-2xl font-black">Mapa de componentes</p>
                            </div>
                            <span class="rounded-md bg-zinc-950 px-3 py-1 text-xs font-bold text-amber-300">Laravel + Filament</span>
                        </div>

                        <div class="mt-5 grid grid-cols-[1fr_0.7fr_1fr] gap-4">
                            <div class="space-y-4">
                                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-xs font-bold uppercase text-sky-700">Entrada</p>
                                    <p class="mt-2 text-lg font-black">Panel Filament</p>
                                    <p class="mt-1 text-sm text-zinc-600">Resources, Pages, Widgets, permisos y formularios.</p>
                                </div>
                                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-xs font-bold uppercase text-emerald-700">Reportes</p>
                                    <p class="mt-2 text-lg font-black">CSV auditables</p>
                                    <p class="mt-1 text-sm text-zinc-600">Libro auxiliar, terceros y balance.</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-center">
                                <div class="grid size-44 place-items-center rounded-lg border-2 border-zinc-950 bg-amber-300 p-5 text-center">
                                    <p class="text-xs font-black uppercase text-zinc-700">Dominio</p>
                                    <p class="mt-2 text-2xl font-black leading-7">Servicios contables</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-xs font-bold uppercase text-amber-700">Persistencia</p>
                                    <p class="mt-2 text-lg font-black">PostgreSQL ready</p>
                                    <p class="mt-1 text-sm text-zinc-600">Comprobantes, asientos, terceros y reglas.</p>
                                </div>
                                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-xs font-bold uppercase text-rose-700">Rendimiento</p>
                                    <p class="mt-2 text-lg font-black">Redis ready</p>
                                    <p class="mt-1 text-sm text-zinc-600">Cache, sesiones y colas configurables.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <section id="capas" class="border-b border-white/10 bg-white px-5 py-16 text-zinc-950 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase text-sky-700">Capas del sistema</p>
                    <h2 class="mt-3 text-4xl font-black leading-tight md:text-5xl">Haz clic y abre la capa que quieras entender.</h2>
                    <p class="mt-4 text-lg leading-8 text-zinc-600">La interfaz captura intención; el dominio decide cómo se registran, validan y protegen los movimientos contables.</p>
                </div>

                <div class="mt-10 grid gap-4 lg:grid-cols-4">
                    @foreach ([
                        ['01', 'Interfaz operativa', 'Filament v5 organiza catálogos, formularios, tablas, widgets y páginas de reportes. No contiene la lógica contable crítica.'],
                        ['02', 'Dominio contable', 'Actions y services causan ingresos, egresos, pagos y ajustes dentro de transacciones de base de datos.'],
                        ['03', 'Persistencia', 'PostgreSQL es el objetivo principal; las migraciones se mantienen portables y con relaciones explícitas.'],
                        ['04', 'Observabilidad contable', 'Reportes CSV, estados de comprobante e inmutabilidad permiten revisar historia sin borrar evidencia.'],
                    ] as [$step, $title, $description])
                        <details class="group rounded-md border border-zinc-200 bg-zinc-50 p-5 open:bg-zinc-950 open:text-white">
                            <summary class="cursor-pointer list-none">
                                <span class="font-mono text-sm font-black text-amber-700 group-open:text-amber-300">{{ $step }}</span>
                                <span class="mt-4 block text-xl font-black">{{ $title }}</span>
                                <span class="mt-3 block text-sm font-semibold text-zinc-500 group-open:text-zinc-300">Abrir detalle</span>
                            </summary>
                            <p class="mt-5 text-sm leading-7 text-zinc-300">{{ $description }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="servicios" class="border-b border-white/10 bg-zinc-950 px-5 py-16 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                    <div>
                        <p class="text-sm font-black uppercase text-amber-300">Servicios de dominio</p>
                        <h2 class="mt-3 text-4xl font-black leading-tight md:text-5xl">El comportamiento vive fuera del formulario.</h2>
                        <p class="mt-4 text-lg leading-8 text-zinc-300">Cada operación relevante pasa por servicios explícitos. Esto evita que una pantalla cree asientos incompletos o salte reglas de periodo, partida doble y trazabilidad.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['ValidateColombianTaxId', 'Valida identificación y DV DIAN cuando aplica.'],
                            ['PostIncomeVoucher', 'Causa ingreso y genera asiento balanceado.'],
                            ['PostExpenseVoucher', 'Causa egreso, soporte, deducibilidad y cuenta por pagar.'],
                            ['ApplyWithholdingRules', 'Aplica reglas versionadas por vigencia, base y tarifa.'],
                            ['RegisterPayment', 'Registra pago contra caja o banco y método de pago.'],
                            ['CreateAdjustmentVoucher', 'Corrige con nota de ajuste sin editar el origen.'],
                            ['PostsBalancedVoucher', 'Protege el principio de partida doble.'],
                            ['EnsureOpenAccountingPeriod', 'Bloquea operación directa en periodos cerrados.'],
                        ] as [$title, $description])
                            <article class="rounded-md border border-white/10 bg-white/5 p-5">
                                <h3 class="font-mono text-sm font-black text-amber-200">{{ $title }}</h3>
                                <p class="mt-3 text-sm leading-6 text-zinc-300">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="flujos" class="border-b border-zinc-200 bg-amber-300 px-5 py-16 text-zinc-950 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase">Flujos críticos</p>
                    <h2 class="mt-3 text-4xl font-black leading-tight md:text-5xl">Tres caminos, una misma regla: trazabilidad antes que velocidad.</h2>
                </div>

                <div class="mt-10 grid gap-4 lg:grid-cols-3">
                    @foreach ([
                        ['Ingreso', ['Tercero validado', 'Cuenta PUC clase 4', 'Contrapartida por cobrar', 'Comprobante aprobado']],
                        ['Egreso', ['Soporte y deducibilidad', 'Gasto/costo clase 5 o 6', 'Retenciones versionadas', 'Cuenta por pagar']],
                        ['Pago / ajuste', ['Caja o banco clase 11', 'Medio de pago trazable', 'Alerta no bancarizada', 'Ajuste si ya fue aprobado']],
                    ] as [$title, $steps])
                        <details class="group rounded-md border-2 border-zinc-950 bg-white p-5 open:bg-zinc-950 open:text-white">
                            <summary class="cursor-pointer list-none text-2xl font-black">
                                {{ $title }}
                                <span class="mt-2 block text-sm font-semibold text-zinc-600 group-open:text-amber-200">Expandir flujo</span>
                            </summary>
                            <ol class="mt-6 space-y-3">
                                @foreach ($steps as $index => $step)
                                    <li class="flex gap-3 text-sm font-bold">
                                        <span class="grid size-7 shrink-0 place-items-center rounded-md bg-zinc-950 font-mono text-xs text-amber-300 group-open:bg-amber-300 group-open:text-zinc-950">{{ $index + 1 }}</span>
                                        <span class="pt-1">{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="garantias" class="bg-white px-5 py-16 text-zinc-950 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                    <div>
                        <p class="text-sm font-black uppercase text-rose-700">Garantías técnicas</p>
                        <h2 class="mt-3 text-4xl font-black leading-tight md:text-5xl">Lo público explica estructura, no expone operación.</h2>
                        <p class="mt-4 text-lg leading-8 text-zinc-600">La landing documenta decisiones del sistema sin revelar terceros, cuentas reales, comprobantes reales, saldos, usuarios internos ni datos de empresa.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['Sin datos sensibles', 'La página usa conceptos, nombres de módulos y patrones. No muestra información operacional.'],
                            ['Asientos inmutables', 'Lo aprobado se corrige con nota de ajuste, no con edición directa.'],
                            ['Reglas configurables', 'Retenciones y vigencias viven en configuración versionada.'],
                            ['Reportes exportables', 'CSV UTF-8 preparado para revisión contable y auditoría.'],
                            ['PostgreSQL ready', 'Base principal objetivo para producción.'],
                            ['Redis ready', 'Cache, colas y sesiones pueden moverse a Redis por entorno.'],
                        ] as [$title, $description])
                            <article class="rounded-md border border-zinc-200 bg-zinc-50 p-5">
                                <h3 class="text-lg font-black">{{ $title }}</h3>
                                <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10 bg-zinc-950 px-5 py-8 text-sm text-zinc-400 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p>ContPass expone arquitectura y alcance funcional. La operación real vive protegida en `/admin`.</p>
            <a href="{{ url('/admin') }}" class="font-bold text-amber-200 hover:text-white">Abrir panel administrativo</a>
        </div>
    </footer>
</body>
</html>

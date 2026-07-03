<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Contabilidad interna' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-950">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 border-r border-zinc-200 bg-white lg:block">
            <div class="border-b border-zinc-200 px-5 py-4">
                <p class="text-sm font-semibold text-zinc-500">Contabilidad</p>
                <p class="text-lg font-bold">Control interno</p>
            </div>
            <nav class="grid gap-1 p-3 text-sm">
                @foreach ([
                    'dashboard' => 'Dashboard',
                    'third-parties.index' => 'Terceros',
                    'chart-accounts.index' => 'PUC',
                    'cash-accounts.index' => 'Caja y bancos',
                    'withholding-rules.index' => 'Retenciones',
                    'income-records.index' => 'Ingresos',
                    'expense-records.index' => 'Egresos',
                    'payments.index' => 'Pagos',
                    'accounting-reports.ledger' => 'Libro auxiliar',
                    'accounting-reports.trial-balance' => 'Balance',
                ] as $route => $label)
                    <a href="{{ route($route) }}" class="rounded-md px-3 py-2 {{ request()->routeIs($route) ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-zinc-700 hover:bg-zinc-100' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>
        <main class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-3">
                <div>
                    <h1 class="text-xl font-semibold">{{ $title ?? 'Contabilidad interna' }}</h1>
                    <p class="text-sm text-zinc-500">MVP Colombia 2026</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-50">Salir</button>
                </form>
            </header>
            <section class="p-4 lg:p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                        <p class="font-semibold">Revisa los datos:</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </section>
        </main>
    </div>
</body>
</html>

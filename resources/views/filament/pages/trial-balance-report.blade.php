<x-filament-panels::page>
    @php($rows = $this->rows())
    @php($debitTotal = $rows->sum(fn ($row) => (float) $row->debit_total))
    @php($creditTotal = $rows->sum(fn ($row) => (float) $row->credit_total))

    <section class="space-y-4">
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 md:grid-cols-5">
                <label class="space-y-1.5">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Desde</span>
                    <input name="starts_on" type="date" value="{{ request('starts_on') }}" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </label>

                <label class="space-y-1.5">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Hasta</span>
                    <input name="ends_on" type="date" value="{{ request('ends_on') }}" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </label>

                <div class="flex items-end gap-2 md:col-span-3">
                    <button class="inline-flex h-10 items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        Filtrar
                    </button>
                    <a href="{{ route('accounting-reports.trial-balance', array_merge(request()->query(), ['export' => 1])) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:hover:bg-white/10">
                        CSV
                    </a>
                </div>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">Débitos</p>
                <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">COP ${{ number_format($debitTotal, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">Créditos</p>
                <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">COP ${{ number_format($creditTotal, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">Diferencia</p>
                <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">COP ${{ number_format($debitTotal - $creditTotal, 2) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">Balance por cuenta</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($rows->count()) }} cuentas con movimiento</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[840px] w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3 text-right">Débito</th>
                            <th class="px-4 py-3 text-right">Crédito</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($rows as $row)
                            @php($balance = (float) $row->debit_total - (float) $row->credit_total)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $row->code }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $row->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">COP ${{ number_format((float) $row->debit_total, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">COP ${{ number_format((float) $row->credit_total, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">COP ${{ number_format($balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No hay cuentas con movimiento para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-filament-panels::page>

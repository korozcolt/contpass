<x-filament-panels::page>
    @php($entries = $this->entries())

    <section class="space-y-4">
        <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 lg:grid-cols-12">
                <label class="space-y-1.5 lg:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Desde</span>
                    <input name="starts_on" type="date" value="{{ request('starts_on') }}" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </label>

                <label class="space-y-1.5 lg:col-span-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Hasta</span>
                    <input name="ends_on" type="date" value="{{ request('ends_on') }}" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </label>

                <label class="space-y-1.5 lg:col-span-5">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tercero</span>
                    <select name="third_party_id" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="">Todos los terceros</option>
                        @foreach ($this->thirdParties() as $thirdParty)
                            <option value="{{ $thirdParty->id }}" @selected(request('third_party_id') == $thirdParty->id)>{{ $thirdParty->tax_id }} · {{ $thirdParty->name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end gap-2 lg:col-span-3">
                    <button class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        Filtrar
                    </button>
                    <a href="{{ route('accounting-reports.third-party-movements', array_merge(request()->query(), ['export' => 1])) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:hover:bg-white/10">
                        CSV
                    </a>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">Movimientos por tercero</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($entries->total()) }} registros encontrados</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1040px] w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Tercero</th>
                            <th class="px-4 py-3">Comprobante</th>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3 text-right">Débito</th>
                            <th class="px-4 py-3 text-right">Crédito</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($entries as $entry)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $entry->voucher->date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $entry->thirdParty?->name ?? 'Sin tercero' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $entry->voucher->number }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $entry->chartAccount->full_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">COP ${{ number_format((float) $entry->debit, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">COP ${{ number_format((float) $entry->credit, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No hay movimientos por tercero para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $entries->links() }}
        </div>
    </section>
</x-filament-panels::page>

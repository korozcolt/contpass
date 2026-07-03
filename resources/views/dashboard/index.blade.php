<x-layouts.app title="Dashboard">
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([['Ingresos', $incomeTotal], ['Egresos', $expenseTotal], ['Pagos', $paymentTotal], ['Comprobantes', $voucherCount]] as [$label, $value])
            <div class="rounded-lg border border-zinc-200 bg-white p-4">
                <p class="text-sm text-zinc-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="font-semibold">Últimos movimientos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-500">
                    <tr><th class="px-4 py-2">Fecha</th><th>Comprobante</th><th>Cuenta</th><th>Tercero</th><th class="text-right">Débito</th><th class="px-4 text-right">Crédito</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentEntries as $entry)
                        <tr class="border-t border-zinc-100">
                            <td class="px-4 py-2">{{ $entry->voucher->date->format('Y-m-d') }}</td>
                            <td>{{ $entry->voucher->number }}</td>
                            <td>{{ $entry->chartAccount->full_name }}</td>
                            <td>{{ $entry->thirdParty?->name ?? 'Sin tercero' }}</td>
                            <td class="text-right">{{ number_format((float) $entry->debit, 2) }}</td>
                            <td class="px-4 text-right">{{ number_format((float) $entry->credit, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">Aún no hay movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>

<x-filament-panels::page>
    <form method="GET" class="flex flex-wrap gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <input name="starts_on" type="date" value="{{ request('starts_on') }}" class="rounded-lg border-gray-300">
        <input name="ends_on" type="date" value="{{ request('ends_on') }}" class="rounded-lg border-gray-300">
        <button class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white">Filtrar</button>
        <a href="{{ route('accounting-reports.trial-balance', array_merge(request()->query(), ['export' => 1])) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">CSV</a>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-500"><tr><th class="px-4 py-2">Cuenta</th><th>Nombre</th><th class="text-right">Débito</th><th class="text-right">Crédito</th><th class="px-4 text-right">Saldo</th></tr></thead>
            <tbody>
                @foreach ($this->rows() as $row)
                    @php($balance = (float) $row->debit_total - (float) $row->credit_total)
                    <tr class="border-t border-gray-100"><td class="px-4 py-2 font-mono">{{ $row->code }}</td><td>{{ $row->name }}</td><td class="text-right">COP ${{ number_format((float) $row->debit_total, 2) }}</td><td class="text-right">COP ${{ number_format((float) $row->credit_total, 2) }}</td><td class="px-4 text-right">COP ${{ number_format($balance, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>

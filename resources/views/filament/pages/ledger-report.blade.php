<x-filament-panels::page>
    <form method="GET" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-5">
        <input name="starts_on" type="date" value="{{ request('starts_on') }}" class="rounded-lg border-gray-300">
        <input name="ends_on" type="date" value="{{ request('ends_on') }}" class="rounded-lg border-gray-300">
        <select name="chart_account_id" class="rounded-lg border-gray-300">
            <option value="">Todas las cuentas</option>
            @foreach ($this->chartAccounts() as $account)
                <option value="{{ $account->id }}" @selected(request('chart_account_id') == $account->id)>{{ $account->full_name }}</option>
            @endforeach
        </select>
        <select name="third_party_id" class="rounded-lg border-gray-300">
            <option value="">Todos los terceros</option>
            @foreach ($this->thirdParties() as $thirdParty)
                <option value="{{ $thirdParty->id }}" @selected(request('third_party_id') == $thirdParty->id)>{{ $thirdParty->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white">Filtrar</button>
            <a href="{{ route('accounting-reports.ledger', array_merge(request()->query(), ['export' => 1])) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">CSV</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-500"><tr><th class="px-4 py-2">Fecha</th><th>Comprobante</th><th>Cuenta</th><th>Tercero</th><th>Descripción</th><th class="text-right">Débito</th><th class="px-4 text-right">Crédito</th></tr></thead>
            <tbody>
                @foreach ($this->entries() as $entry)
                    <tr class="border-t border-gray-100"><td class="px-4 py-2">{{ $entry->voucher->date->format('Y-m-d') }}</td><td>{{ $entry->voucher->number }}</td><td>{{ $entry->chartAccount->full_name }}</td><td>{{ $entry->thirdParty?->name }}</td><td>{{ $entry->description }}</td><td class="text-right">COP ${{ number_format((float) $entry->debit, 2) }}</td><td class="px-4 text-right">COP ${{ number_format((float) $entry->credit, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $this->entries()->links() }}
</x-filament-panels::page>

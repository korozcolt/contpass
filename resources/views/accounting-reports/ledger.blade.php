<x-layouts.app title="Libro auxiliar">
    <form class="mb-4 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 md:grid-cols-5">
        <input name="starts_on" type="date" value="{{ $filters['starts_on'] ?? '' }}" class="rounded-md border-zinc-300">
        <input name="ends_on" type="date" value="{{ $filters['ends_on'] ?? '' }}" class="rounded-md border-zinc-300">
        <select name="chart_account_id" class="rounded-md border-zinc-300"><option value="">Todas las cuentas</option>@foreach ($chartAccounts as $account)<option value="{{ $account->id }}" @selected(($filters['chart_account_id'] ?? '') == $account->id)>{{ $account->full_name }}</option>@endforeach</select>
        <select name="third_party_id" class="rounded-md border-zinc-300"><option value="">Todos los terceros</option>@foreach ($thirdParties as $thirdParty)<option value="{{ $thirdParty->id }}" @selected(($filters['third_party_id'] ?? '') == $thirdParty->id)>{{ $thirdParty->name }}</option>@endforeach</select>
        <div class="flex gap-2"><button class="rounded-md bg-zinc-800 px-3 py-2 text-sm font-semibold text-white">Filtrar</button><button name="export" value="1" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">CSV</button></div>
    </form>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Fecha</th><th>Comprobante</th><th>Cuenta</th><th>Tercero</th><th>Descripción</th><th class="text-right">Débito</th><th class="px-4 text-right">Crédito</th></tr></thead><tbody>
        @foreach ($entries as $entry)<tr class="border-t border-zinc-100"><td class="px-4 py-2">{{ $entry->voucher->date->format('Y-m-d') }}</td><td>{{ $entry->voucher->number }}</td><td>{{ $entry->chartAccount->full_name }}</td><td>{{ $entry->thirdParty?->name }}</td><td>{{ $entry->description }}</td><td class="text-right">{{ number_format((float) $entry->debit, 2) }}</td><td class="px-4 text-right">{{ number_format((float) $entry->credit, 2) }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="mt-4">{{ $entries->links() }}</div>
</x-layouts.app>

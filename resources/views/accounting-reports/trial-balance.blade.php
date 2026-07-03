<x-layouts.app title="Balance de comprobación">
    <form class="mb-4 flex flex-wrap gap-3 rounded-lg border border-zinc-200 bg-white p-4">
        <input name="starts_on" type="date" value="{{ $filters['starts_on'] ?? '' }}" class="rounded-md border-zinc-300">
        <input name="ends_on" type="date" value="{{ $filters['ends_on'] ?? '' }}" class="rounded-md border-zinc-300">
        <button class="rounded-md bg-zinc-800 px-3 py-2 text-sm font-semibold text-white">Filtrar</button>
        <button name="export" value="1" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">CSV</button>
    </form>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Cuenta</th><th>Nombre</th><th class="text-right">Débito</th><th class="text-right">Crédito</th><th class="px-4 text-right">Saldo</th></tr></thead><tbody>
        @foreach ($rows as $row)<tr class="border-t border-zinc-100"><td class="px-4 py-2 font-mono">{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-right">{{ number_format($row['debit_total'], 2) }}</td><td class="text-right">{{ number_format($row['credit_total'], 2) }}</td><td class="px-4 text-right">{{ number_format($row['balance'], 2) }}</td></tr>@endforeach
    </tbody></table></div>
</x-layouts.app>

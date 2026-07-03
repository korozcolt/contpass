@props(['voucher'])

<div class="mt-5 overflow-x-auto rounded-lg border border-zinc-200 bg-white">
    <table class="w-full text-left text-sm">
        <thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Cuenta</th><th>Tercero</th><th>Descripción</th><th class="text-right">Débito</th><th class="px-4 text-right">Crédito</th></tr></thead>
        <tbody>
            @foreach ($voucher->entries as $entry)
                <tr class="border-t border-zinc-100"><td class="px-4 py-2">{{ $entry->chartAccount->full_name }}</td><td>{{ $entry->thirdParty?->name }}</td><td>{{ $entry->description }}</td><td class="text-right">{{ number_format((float) $entry->debit, 2) }}</td><td class="px-4 text-right">{{ number_format((float) $entry->credit, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>

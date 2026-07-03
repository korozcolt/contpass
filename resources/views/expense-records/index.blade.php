<x-layouts.app title="Egresos">
    <div class="mb-4 flex justify-end"><a href="{{ route('expense-records.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Causar egreso</a></div>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Fecha</th><th>Comprobante</th><th>Tercero</th><th>Soporte</th><th class="text-right">Valor</th><th class="text-right">Retención</th></tr></thead><tbody>
        @foreach ($expenseRecords as $record)<tr class="border-t border-zinc-100"><td class="px-4 py-2">{{ $record->accrual_date->format('Y-m-d') }}</td><td><a class="text-emerald-700" href="{{ route('expense-records.show', $record) }}">{{ $record->voucher->number }}</a></td><td>{{ $record->voucher->thirdParty?->name }}</td><td>{{ $record->support_type }} {{ $record->support_number }}</td><td class="text-right">{{ number_format((float) $record->amount, 2) }}</td><td class="px-4 text-right">{{ number_format((float) $record->withholding_amount, 2) }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="mt-4">{{ $expenseRecords->links() }}</div>
</x-layouts.app>

<x-layouts.app title="Reglas de retención">
    <div class="mb-4 flex justify-end"><a href="{{ route('withholding-rules.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Nueva regla</a></div>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Concepto</th><th>Base mínima</th><th>Tarifa</th><th>Cuenta</th><th>Vigencia</th><th></th></tr></thead><tbody>
        @foreach ($withholdingRules as $rule)<tr class="border-t border-zinc-100"><td class="px-4 py-2 font-medium">{{ $rule->concept }}</td><td>{{ number_format((float) $rule->minimum_base, 2) }}</td><td>{{ number_format((float) $rule->rate, 4) }}%</td><td>{{ $rule->chartAccount->full_name }}</td><td>{{ $rule->starts_on->format('Y-m-d') }} / {{ $rule->ends_on?->format('Y-m-d') ?? 'abierta' }}</td><td class="px-4 text-right"><a class="text-emerald-700" href="{{ route('withholding-rules.edit', $rule) }}">Editar</a></td></tr>@endforeach
    </tbody></table></div>
    <div class="mt-4">{{ $withholdingRules->links() }}</div>
</x-layouts.app>

<x-layouts.app title="Caja y bancos">
    <div class="mb-4 flex justify-end"><a href="{{ route('cash-accounts.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Nueva cuenta</a></div>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Nombre</th><th>Tipo</th><th>Cuenta PUC</th><th>Banco</th><th></th></tr></thead><tbody>
        @foreach ($cashAccounts as $cashAccount)<tr class="border-t border-zinc-100"><td class="px-4 py-2 font-medium">{{ $cashAccount->name }}</td><td>{{ $cashAccount->type->label() }}</td><td>{{ $cashAccount->chartAccount->full_name }}</td><td>{{ $cashAccount->bank_name }}</td><td class="px-4 text-right"><a class="text-emerald-700" href="{{ route('cash-accounts.edit', $cashAccount) }}">Editar</a></td></tr>@endforeach
    </tbody></table></div>
    <div class="mt-4">{{ $cashAccounts->links() }}</div>
</x-layouts.app>

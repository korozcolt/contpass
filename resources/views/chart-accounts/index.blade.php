<x-layouts.app title="Plan Único de Cuentas">
    <div class="mb-4 flex justify-end"><a href="{{ route('chart-accounts.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Nueva cuenta</a></div>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Código</th><th>Nombre</th><th>Naturaleza</th><th>Activa</th><th></th></tr></thead><tbody>
            @foreach ($chartAccounts as $account)<tr class="border-t border-zinc-100"><td class="px-4 py-2 font-mono">{{ $account->code }}</td><td>{{ $account->name }}</td><td>{{ $account->nature->label() }}</td><td>{{ $account->is_active ? 'Sí' : 'No' }}</td><td class="px-4 text-right"><a class="text-emerald-700" href="{{ route('chart-accounts.edit', $account) }}">Editar</a></td></tr>@endforeach
        </tbody></table>
    </div>
    <div class="mt-4">{{ $chartAccounts->links() }}</div>
</x-layouts.app>

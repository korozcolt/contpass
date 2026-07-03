<x-layouts.app title="Terceros">
    <div class="mb-4 flex justify-end"><a href="{{ route('third-parties.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Nuevo tercero</a></div>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Nombre</th><th>Tipo</th><th>NIT/Cédula</th><th>Ciudad</th><th></th></tr></thead>
            <tbody>
                @forelse ($thirdParties as $thirdParty)
                    <tr class="border-t border-zinc-100"><td class="px-4 py-2 font-medium">{{ $thirdParty->name }}</td><td>{{ $thirdParty->type->label() }}</td><td>{{ $thirdParty->tax_id }}-{{ $thirdParty->verification_digit }}</td><td>{{ $thirdParty->city }}</td><td class="px-4 text-right"><a class="text-emerald-700" href="{{ route('third-parties.edit', $thirdParty) }}">Editar</a></td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-500">Sin terceros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $thirdParties->links() }}</div>
</x-layouts.app>

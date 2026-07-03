<x-layouts.app :title="$chartAccount->exists ? 'Editar cuenta PUC' : 'Nueva cuenta PUC'">
    <form method="POST" action="{{ $chartAccount->exists ? route('chart-accounts.update', $chartAccount) : route('chart-accounts.store') }}" class="max-w-2xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @if ($chartAccount->exists) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Código<input name="code" value="{{ old('code', $chartAccount->code) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Naturaleza<select name="nature" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($natures as $nature)<option value="{{ $nature->value }}" @selected(old('nature', $chartAccount->nature?->value) === $nature->value)>{{ $nature->label() }}</option>@endforeach</select></label>
            <label class="block text-sm md:col-span-2">Nombre<input name="name" value="{{ old('name', $chartAccount->name) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $chartAccount->is_active ?? true))> Activa</label>
        </div>
        <div class="mt-5 flex gap-3"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Guardar</button><a href="{{ route('chart-accounts.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancelar</a></div>
    </form>
</x-layouts.app>

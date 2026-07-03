<x-layouts.app :title="$thirdParty->exists ? 'Editar tercero' : 'Nuevo tercero'">
    <form method="POST" action="{{ $thirdParty->exists ? route('third-parties.update', $thirdParty) : route('third-parties.store') }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @if ($thirdParty->exists) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Tipo<select name="type" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($types as $type)<option value="{{ $type->value }}" @selected(old('type', $thirdParty->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach</select></label>
            <label class="block text-sm">Nombre<input name="name" value="{{ old('name', $thirdParty->name) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">NIT/Cédula<input name="tax_id" value="{{ old('tax_id', $thirdParty->tax_id) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">DV<input name="verification_digit" type="number" min="0" max="9" value="{{ old('verification_digit', $thirdParty->verification_digit) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="block text-sm">Correo<input name="email" type="email" value="{{ old('email', $thirdParty->email) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="block text-sm">Teléfono<input name="phone" value="{{ old('phone', $thirdParty->phone) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="block text-sm">Ciudad<input name="city" value="{{ old('city', $thirdParty->city) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="block text-sm md:col-span-2">Dirección<input name="address" value="{{ old('address', $thirdParty->address) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
        </div>
        <div class="mt-5 flex gap-3"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Guardar</button><a href="{{ route('third-parties.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancelar</a></div>
    </form>
</x-layouts.app>

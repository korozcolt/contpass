<x-layouts.app :title="$cashAccount->exists ? 'Editar caja/banco' : 'Nueva caja/banco'">
    <form method="POST" action="{{ $cashAccount->exists ? route('cash-accounts.update', $cashAccount) : route('cash-accounts.store') }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @if ($cashAccount->exists) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Nombre<input name="name" value="{{ old('name', $cashAccount->name) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Tipo<select name="type" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($types as $type)<option value="{{ $type->value }}" @selected(old('type', $cashAccount->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach</select></label>
            <label class="block text-sm md:col-span-2">Cuenta PUC<select name="chart_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}" @selected((int) old('chart_account_id', $cashAccount->chart_account_id) === $account->id)>{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Banco<input name="bank_name" value="{{ old('bank_name', $cashAccount->bank_name) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="block text-sm">Número<input name="account_number" value="{{ old('account_number', $cashAccount->account_number) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $cashAccount->is_active ?? true))> Activa</label>
        </div>
        <div class="mt-5 flex gap-3"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Guardar</button><a href="{{ route('cash-accounts.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancelar</a></div>
    </form>
</x-layouts.app>

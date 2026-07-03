<x-layouts.app :title="$withholdingRule->exists ? 'Editar retención' : 'Nueva retención'">
    <form method="POST" action="{{ $withholdingRule->exists ? route('withholding-rules.update', $withholdingRule) : route('withholding-rules.store') }}" class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        @if ($withholdingRule->exists) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Concepto<input name="concept" value="{{ old('concept', $withholdingRule->concept) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Cuenta de retención<select name="chart_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}" @selected((int) old('chart_account_id', $withholdingRule->chart_account_id) === $account->id)>{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Base mínima<input name="minimum_base" type="number" step="0.01" min="0" value="{{ old('minimum_base', $withholdingRule->minimum_base ?? 0) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Tarifa %<input name="rate" type="number" step="0.0001" min="0" max="100" value="{{ old('rate', $withholdingRule->rate) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Inicio<input name="starts_on" type="date" value="{{ old('starts_on', $withholdingRule->starts_on?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Fin<input name="ends_on" type="date" value="{{ old('ends_on', $withholdingRule->ends_on?->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $withholdingRule->is_active ?? true))> Activa</label>
        </div>
        <div class="mt-5 flex gap-3"><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Guardar</button><a href="{{ route('withholding-rules.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancelar</a></div>
    </form>
</x-layouts.app>

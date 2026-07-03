<x-layouts.app title="Causar egreso">
    <form method="POST" action="{{ route('expense-records.store') }}" class="max-w-4xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Tercero<select name="third_party_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($thirdParties as $thirdParty)<option value="{{ $thirdParty->id }}">{{ $thirdParty->name }} · {{ $thirdParty->tax_id }}</option>@endforeach</select></label>
            <label class="block text-sm">Fecha de causación<input name="accrual_date" type="date" value="{{ old('accrual_date', now()->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Cuenta de gasto/costo<select name="expense_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}">{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Cuenta por pagar<select name="payable_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}">{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Tipo soporte<input name="support_type" value="{{ old('support_type', 'Factura/Cuenta de cobro') }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Número soporte<input name="support_number" value="{{ old('support_number') }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Valor<input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Descripción<input name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="flex items-center gap-2 text-sm"><input name="has_valid_support" type="checkbox" value="1" @checked(old('has_valid_support', true))> Soporte idóneo</label>
            <label class="flex items-center gap-2 text-sm"><input name="is_deductible" type="checkbox" value="1" @checked(old('is_deductible', true))> Deducible</label>
        </div>
        <button class="mt-5 rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Causar</button>
    </form>
</x-layouts.app>

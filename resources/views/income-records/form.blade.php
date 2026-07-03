<x-layouts.app title="Causar ingreso">
    <form method="POST" action="{{ route('income-records.store') }}" class="max-w-4xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Tercero<select name="third_party_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($thirdParties as $thirdParty)<option value="{{ $thirdParty->id }}">{{ $thirdParty->name }} · {{ $thirdParty->tax_id }}</option>@endforeach</select></label>
            <label class="block text-sm">Fecha de causación<input name="accrual_date" type="date" value="{{ old('accrual_date', now()->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Cuenta de ingreso<select name="revenue_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}">{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Cuenta por cobrar<select name="receivable_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}">{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Soporte<input name="support_number" value="{{ old('support_number') }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Valor<input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm md:col-span-2">Descripción<input name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
        </div>
        <button class="mt-5 rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Causar</button>
    </form>
</x-layouts.app>

<x-layouts.app title="Registrar pago">
    <form method="POST" action="{{ route('payments.store') }}" class="max-w-4xl rounded-lg border border-zinc-200 bg-white p-5">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm">Comprobante origen<select name="source_voucher_id" class="mt-1 w-full rounded-md border-zinc-300"><option value="">Sin origen</option>@foreach ($vouchers as $voucher)<option value="{{ $voucher->id }}">{{ $voucher->number }} · {{ $voucher->thirdParty?->name }} · {{ $voucher->description }}</option>@endforeach</select></label>
            <label class="block text-sm">Caja/Banco<select name="cash_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($cashAccounts as $cashAccount)<option value="{{ $cashAccount->id }}">{{ $cashAccount->name }}</option>@endforeach</select></label>
            <label class="block text-sm">Contrapartida<select name="counterparty_account_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($chartAccounts as $account)<option value="{{ $account->id }}">{{ $account->full_name }}</option>@endforeach</select></label>
            <label class="block text-sm">Método<select name="method" class="mt-1 w-full rounded-md border-zinc-300">@foreach ($methods as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach</select></label>
            <label class="block text-sm">Referencia<input name="reference" value="{{ old('reference') }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            <label class="block text-sm">Fecha<input name="paid_on" type="date" value="{{ old('paid_on', now()->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Valor<input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-md border-zinc-300" required></label>
            <label class="block text-sm">Descripción<input name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
        </div>
        <button class="mt-5 rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Registrar</button>
    </form>
</x-layouts.app>

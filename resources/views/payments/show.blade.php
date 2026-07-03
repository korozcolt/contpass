<x-layouts.app title="Pago {{ $payment->voucher->number }}">
    <div class="rounded-lg border border-zinc-200 bg-white p-5">
        <h2 class="text-lg font-semibold">{{ $payment->voucher->description }}</h2>
        <p class="mt-2 text-sm text-zinc-600">{{ $payment->paid_on->format('Y-m-d') }} · {{ $payment->cashAccount->name }} · {{ $payment->method->label() }} · {{ number_format((float) $payment->amount, 2) }}</p>
        <p class="mt-2 text-sm {{ $payment->is_bancarized ? 'text-emerald-700' : 'text-amber-700' }}">{{ $payment->is_bancarized ? 'Medio bancarizado' : 'Pago en efectivo: revisar límites del Art. 771-5 E.T.' }}</p>
    </div>
    <x-voucher-entries :voucher="$payment->voucher" />
</x-layouts.app>

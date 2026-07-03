<x-layouts.app title="Pagos">
    <div class="mb-4 flex justify-end"><a href="{{ route('payments.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Registrar pago</a></div>
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-4 py-2">Fecha</th><th>Comprobante</th><th>Caja/Banco</th><th>Método</th><th class="text-right">Valor</th><th>Bancarizado</th></tr></thead><tbody>
        @foreach ($payments as $payment)<tr class="border-t border-zinc-100"><td class="px-4 py-2">{{ $payment->paid_on->format('Y-m-d') }}</td><td><a class="text-emerald-700" href="{{ route('payments.show', $payment) }}">{{ $payment->voucher->number }}</a></td><td>{{ $payment->cashAccount->name }}</td><td>{{ $payment->method->label() }}</td><td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td><td class="px-4">{{ $payment->is_bancarized ? 'Sí' : 'No' }}</td></tr>@endforeach
    </tbody></table></div>
    <div class="mt-4">{{ $payments->links() }}</div>
</x-layouts.app>

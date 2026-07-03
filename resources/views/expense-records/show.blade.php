<x-layouts.app title="Egreso {{ $expenseRecord->voucher->number }}">
    <div class="rounded-lg border border-zinc-200 bg-white p-5">
        <h2 class="text-lg font-semibold">{{ $expenseRecord->voucher->description }}</h2>
        <p class="mt-2 text-sm text-zinc-600">{{ $expenseRecord->accrual_date->format('Y-m-d') }} · {{ $expenseRecord->voucher->thirdParty?->name }} · Valor {{ number_format((float) $expenseRecord->amount, 2) }} · Retención {{ number_format((float) $expenseRecord->withholding_amount, 2) }}</p>
        <p class="mt-2 text-sm text-zinc-600">Soporte {{ $expenseRecord->support_type }} {{ $expenseRecord->support_number }} · {{ $expenseRecord->has_valid_support ? 'Soporte idóneo' : 'Soporte pendiente' }} · {{ $expenseRecord->is_deductible ? 'Deducible' : 'No deducible' }}</p>
    </div>
    <x-voucher-entries :voucher="$expenseRecord->voucher" />
</x-layouts.app>

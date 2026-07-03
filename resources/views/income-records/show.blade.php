<x-layouts.app title="Ingreso {{ $incomeRecord->voucher->number }}">
    <div class="rounded-lg border border-zinc-200 bg-white p-5">
        <h2 class="text-lg font-semibold">{{ $incomeRecord->voucher->description }}</h2>
        <p class="mt-2 text-sm text-zinc-600">{{ $incomeRecord->accrual_date->format('Y-m-d') }} · {{ $incomeRecord->voucher->thirdParty?->name }} · {{ number_format((float) $incomeRecord->amount, 2) }}</p>
    </div>
    <x-voucher-entries :voucher="$incomeRecord->voucher" />
</x-layouts.app>

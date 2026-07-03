<x-layouts.app title="Tercero">
    <div class="rounded-lg border border-zinc-200 bg-white p-5">
        <h2 class="text-lg font-semibold">{{ $thirdParty->name }}</h2>
        <p class="mt-2 text-sm text-zinc-600">{{ $thirdParty->type->label() }} · {{ $thirdParty->tax_id }}-{{ $thirdParty->verification_digit }}</p>
    </div>
</x-layouts.app>

<?php

namespace App\Services\Accounting;

use App\Enums\WithholdingType;
use App\Models\Company;
use App\Models\WithholdingRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class EnsureNoOverlappingIcaRule
{
    public function handle(Company $company, int $municipalityId, string $startsOn, ?string $endsOn, ?int $ignoreId = null): void
    {
        $overlaps = WithholdingRule::query()
            ->whereBelongsTo($company)
            ->where('type', WithholdingType::Ica)
            ->where('municipality_id', $municipalityId)
            ->where('is_active', true)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('starts_on', '<=', $endsOn ?? '9999-12-31')
            ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $startsOn))
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'starts_on' => 'Ya existe una regla ICA activa para este municipio con vigencia solapada.',
            ]);
        }
    }
}

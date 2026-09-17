<?php

namespace App\Filament\Resources\WithholdingRules\Pages;

use App\Enums\WithholdingType;
use App\Filament\Resources\WithholdingRules\WithholdingRuleResource;
use App\Models\Company;
use App\Services\Accounting\EnsureNoOverlappingIcaRule;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWithholdingRule extends CreateRecord
{
    protected static string $resource = WithholdingRuleResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        if (($data['type'] ?? null) === WithholdingType::Ica) {
            app(EnsureNoOverlappingIcaRule::class)->handle(
                Company::query()->findOrFail((int) $data['company_id']),
                (int) $data['municipality_id'],
                (string) $data['starts_on'],
                $data['ends_on'] ?? null,
            );
        }

        return static::getModel()::create($data);
    }
}

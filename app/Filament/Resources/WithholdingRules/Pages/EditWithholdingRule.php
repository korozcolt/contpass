<?php

namespace App\Filament\Resources\WithholdingRules\Pages;

use App\Enums\WithholdingType;
use App\Filament\Resources\WithholdingRules\WithholdingRuleResource;
use App\Services\Accounting\EnsureNoOverlappingIcaRule;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWithholdingRule extends EditRecord
{
    protected static string $resource = WithholdingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (($data['type'] ?? null) === WithholdingType::Ica) {
            app(EnsureNoOverlappingIcaRule::class)->handle(
                $record->company,
                (int) $data['municipality_id'],
                (string) $data['starts_on'],
                $data['ends_on'] ?? null,
                $record->getKey(),
            );
        }

        $record->update($data);

        return $record;
    }
}

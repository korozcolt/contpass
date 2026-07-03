<?php

namespace App\Filament\Resources\WithholdingRules\Pages;

use App\Filament\Resources\WithholdingRules\WithholdingRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWithholdingRule extends EditRecord
{
    protected static string $resource = WithholdingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\WithholdingRules\Pages;

use App\Filament\Resources\WithholdingRules\WithholdingRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWithholdingRules extends ListRecords
{
    protected static string $resource = WithholdingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

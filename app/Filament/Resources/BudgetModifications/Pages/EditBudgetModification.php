<?php

namespace App\Filament\Resources\BudgetModifications\Pages;

use App\Filament\Resources\BudgetModifications\BudgetModificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudgetModification extends EditRecord
{
    protected static string $resource = BudgetModificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

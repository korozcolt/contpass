<?php

namespace App\Filament\Resources\BudgetModifications\Pages;

use App\Filament\Resources\BudgetModifications\BudgetModificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetModifications extends ListRecords
{
    protected static string $resource = BudgetModificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

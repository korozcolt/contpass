<?php

namespace App\Filament\Resources\BudgetRevenues\Pages;

use App\Filament\Resources\BudgetRevenues\BudgetRevenueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetRevenues extends ListRecords
{
    protected static string $resource = BudgetRevenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

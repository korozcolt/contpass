<?php

namespace App\Filament\Resources\BudgetRevenues\Pages;

use App\Filament\Resources\BudgetRevenues\BudgetRevenueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudgetRevenue extends EditRecord
{
    protected static string $resource = BudgetRevenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

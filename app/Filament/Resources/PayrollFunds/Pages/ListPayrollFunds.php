<?php

namespace App\Filament\Resources\PayrollFunds\Pages;

use App\Filament\Resources\PayrollFunds\PayrollFundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollFunds extends ListRecords
{
    protected static string $resource = PayrollFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

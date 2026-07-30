<?php

namespace App\Filament\Resources\PayrollFunds\Pages;

use App\Filament\Resources\PayrollFunds\PayrollFundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollFund extends EditRecord
{
    protected static string $resource = PayrollFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

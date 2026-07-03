<?php

namespace App\Filament\Resources\ExpenseRecords\Pages;

use App\Filament\Resources\ExpenseRecords\ExpenseRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseRecord extends EditRecord
{
    protected static string $resource = ExpenseRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

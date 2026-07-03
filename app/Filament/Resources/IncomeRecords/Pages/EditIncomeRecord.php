<?php

namespace App\Filament\Resources\IncomeRecords\Pages;

use App\Filament\Resources\IncomeRecords\IncomeRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncomeRecord extends EditRecord
{
    protected static string $resource = IncomeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

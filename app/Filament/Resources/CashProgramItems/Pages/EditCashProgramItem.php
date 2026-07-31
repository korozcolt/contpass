<?php

namespace App\Filament\Resources\CashProgramItems\Pages;

use App\Filament\Resources\CashProgramItems\CashProgramItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashProgramItem extends EditRecord
{
    protected static string $resource = CashProgramItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

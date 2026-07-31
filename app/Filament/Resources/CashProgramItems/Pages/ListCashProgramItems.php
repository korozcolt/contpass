<?php

namespace App\Filament\Resources\CashProgramItems\Pages;

use App\Filament\Resources\CashProgramItems\CashProgramItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashProgramItems extends ListRecords
{
    protected static string $resource = CashProgramItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

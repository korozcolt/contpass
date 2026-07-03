<?php

namespace App\Filament\Resources\IncomeRecords\Pages;

use App\Filament\Resources\IncomeRecords\IncomeRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncomeRecords extends ListRecords
{
    protected static string $resource = IncomeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

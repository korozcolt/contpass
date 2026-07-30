<?php

namespace App\Filament\Resources\WarehouseItems\Pages;

use App\Filament\Resources\WarehouseItems\WarehouseItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseItems extends ListRecords
{
    protected static string $resource = WarehouseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

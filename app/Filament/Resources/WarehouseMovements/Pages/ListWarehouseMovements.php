<?php

namespace App\Filament\Resources\WarehouseMovements\Pages;

use App\Filament\Resources\WarehouseMovements\WarehouseMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseMovements extends ListRecords
{
    protected static string $resource = WarehouseMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

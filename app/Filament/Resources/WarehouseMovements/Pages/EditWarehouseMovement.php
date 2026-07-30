<?php

namespace App\Filament\Resources\WarehouseMovements\Pages;

use App\Filament\Resources\WarehouseMovements\WarehouseMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseMovement extends EditRecord
{
    protected static string $resource = WarehouseMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

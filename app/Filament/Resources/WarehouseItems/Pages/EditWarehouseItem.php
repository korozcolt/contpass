<?php

namespace App\Filament\Resources\WarehouseItems\Pages;

use App\Filament\Resources\WarehouseItems\WarehouseItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseItem extends EditRecord
{
    protected static string $resource = WarehouseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

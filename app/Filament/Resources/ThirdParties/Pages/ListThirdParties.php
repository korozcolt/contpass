<?php

namespace App\Filament\Resources\ThirdParties\Pages;

use App\Filament\Resources\ThirdParties\ThirdPartyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThirdParties extends ListRecords
{
    protected static string $resource = ThirdPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

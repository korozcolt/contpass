<?php

namespace App\Filament\Resources\CompanySignatories\Pages;

use App\Filament\Resources\CompanySignatories\CompanySignatoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanySignatories extends ListRecords
{
    protected static string $resource = CompanySignatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

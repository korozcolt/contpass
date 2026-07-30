<?php

namespace App\Filament\Resources\CompanySignatories\Pages;

use App\Filament\Resources\CompanySignatories\CompanySignatoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanySignatory extends EditRecord
{
    protected static string $resource = CompanySignatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

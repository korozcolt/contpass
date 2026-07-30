<?php

namespace App\Filament\Resources\PayrollConcepts\Pages;

use App\Filament\Resources\PayrollConcepts\PayrollConceptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollConcept extends EditRecord
{
    protected static string $resource = PayrollConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

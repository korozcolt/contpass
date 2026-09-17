<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Descargar PDF')
                ->icon(Heroicon::DocumentArrowDown)
                ->url(fn (): string => route('quotations.pdf', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}

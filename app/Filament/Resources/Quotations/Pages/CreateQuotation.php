<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Services\Accounting\BuildQuotationNumber;
use App\Services\Accounting\CurrentCompany;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = app(CurrentCompany::class)->get();

        $data['number'] = app(BuildQuotationNumber::class)->next($company);
        $data['status'] = QuotationStatus::Draft->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'number' => 'No se pudo generar el número de cotización. Intenta nuevamente.',
            ]);
        }
    }
}

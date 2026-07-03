<?php

namespace App\Filament\Resources\ThirdParties\Pages;

use App\Filament\Resources\ThirdParties\ThirdPartyResource;
use App\Services\Accounting\ValidateColombianTaxId;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateThirdParty extends CreateRecord
{
    protected static string $resource = ThirdPartyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateTaxId($data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateTaxId(array $data): void
    {
        if (! app(ValidateColombianTaxId::class)->passes((string) $data['tax_id'], $data['verification_digit'] ?? null)) {
            throw ValidationException::withMessages([
                'data.verification_digit' => 'El dígito de verificación no coincide con el algoritmo DIAN.',
            ]);
        }
    }
}

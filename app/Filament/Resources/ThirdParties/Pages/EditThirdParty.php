<?php

namespace App\Filament\Resources\ThirdParties\Pages;

use App\Filament\Resources\ThirdParties\ThirdPartyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Services\Accounting\ValidateColombianTaxId;
use Illuminate\Validation\ValidationException;

class EditThirdParty extends EditRecord
{
    protected static string $resource = ThirdPartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! app(ValidateColombianTaxId::class)->passes((string) $data['tax_id'], $data['verification_digit'] ?? null)) {
            throw ValidationException::withMessages([
                'data.verification_digit' => 'El dígito de verificación no coincide con el algoritmo DIAN.',
            ]);
        }

        return $data;
    }
}

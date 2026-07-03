<?php

namespace App\Http\Requests;

use App\Enums\ThirdPartyType;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\ValidateColombianTaxId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreThirdPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        $company = app(CurrentCompany::class)->get();
        $thirdParty = $this->route('third_party');

        return [
            'type' => ['required', Rule::enum(ThirdPartyType::class)],
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:30', Rule::unique('third_parties', 'tax_id')->where('company_id', $company->id)->ignore($thirdParty)],
            'verification_digit' => ['nullable', 'integer', 'between:0,9'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! app(ValidateColombianTaxId::class)->passes((string) $this->input('tax_id'), $this->input('verification_digit'))) {
                    $validator->errors()->add('verification_digit', 'El dígito de verificación no coincide con el algoritmo DIAN.');
                }
            },
        ];
    }
}

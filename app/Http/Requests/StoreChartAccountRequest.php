<?php

namespace App\Http\Requests;

use App\Enums\AccountNature;
use App\Services\Accounting\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartAccountRequest extends FormRequest
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
        $chartAccount = $this->route('chart_account');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('chart_accounts', 'code')->where('company_id', $company->id)->ignore($chartAccount)],
            'name' => ['required', 'string', 'max:255'],
            'nature' => ['required', Rule::enum(AccountNature::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

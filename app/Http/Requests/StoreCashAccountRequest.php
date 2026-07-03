<?php

namespace App\Http\Requests;

use App\Enums\CashAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashAccountRequest extends FormRequest
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
        return [
            'chart_account_id' => ['required', 'exists:chart_accounts,id'],
            'type' => ['required', Rule::enum(CashAccountType::class)],
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

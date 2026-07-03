<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRecordRequest extends FormRequest
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
            'third_party_id' => ['required', 'exists:third_parties,id'],
            'expense_account_id' => ['required', 'exists:chart_accounts,id'],
            'payable_account_id' => ['required', 'exists:chart_accounts,id'],
            'support_type' => ['required', 'string', 'max:120'],
            'support_number' => ['required', 'string', 'max:120'],
            'accrual_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'has_valid_support' => ['sometimes', 'boolean'],
            'is_deductible' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

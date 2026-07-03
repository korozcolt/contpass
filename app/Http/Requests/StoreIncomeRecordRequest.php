<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRecordRequest extends FormRequest
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
            'revenue_account_id' => ['required', 'exists:chart_accounts,id'],
            'receivable_account_id' => ['required', 'exists:chart_accounts,id'],
            'support_number' => ['required', 'string', 'max:120'],
            'accrual_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

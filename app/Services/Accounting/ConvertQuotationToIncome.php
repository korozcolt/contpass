<?php

namespace App\Services\Accounting;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertQuotationToIncome
{
    public function __construct(private readonly PostIncomeVoucher $postIncomeVoucher) {}

    public function handle(Quotation $quotation): Voucher
    {
        return DB::transaction(function () use ($quotation): Voucher {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== QuotationStatus::Accepted || $locked->voucher_id !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Esta cotización ya fue convertida o no está Aceptada.',
                ]);
            }

            $voucher = $this->postIncomeVoucher->handle($locked->company, $locked->thirdParty, [
                'third_party_id' => $locked->third_party_id,
                'revenue_account_id' => $locked->revenue_account_id,
                'receivable_account_id' => $locked->receivable_account_id,
                'support_number' => $locked->number,
                'accrual_date' => now()->toDateString(),
                'amount' => $locked->total,
                'description' => "Conversión de cotización {$locked->number}",
            ]);

            $locked->forceFill([
                'voucher_id' => $voucher->id,
                'status' => QuotationStatus::Converted,
            ])->save();

            return $voucher;
        });
    }
}

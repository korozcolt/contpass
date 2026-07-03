<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\CashAccount;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\RegisterPayment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $cashAccount = CashAccount::query()->findOrFail((int) $data['cash_account_id']);
        $sourceVoucher = filled($data['source_voucher_id'] ?? null)
            ? Voucher::query()->with('thirdParty')->findOrFail((int) $data['source_voucher_id'])
            : null;

        $voucher = app(RegisterPayment::class)->handle(app(CurrentCompany::class)->get(), $cashAccount, $data, $sourceVoucher);

        return $voucher->payment()->firstOrFail();
    }
}

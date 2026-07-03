<?php

namespace App\Filament\Resources\ExpenseRecords\Pages;

use App\Filament\Resources\ExpenseRecords\ExpenseRecordResource;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\PostExpenseVoucher;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpenseRecord extends CreateRecord
{
    protected static string $resource = ExpenseRecordResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $thirdParty = ThirdParty::query()->findOrFail((int) $data['third_party_id']);

        $voucher = app(PostExpenseVoucher::class)->handle(app(CurrentCompany::class)->get(), $thirdParty, $data);

        return $voucher->expenseRecord()->firstOrFail();
    }
}

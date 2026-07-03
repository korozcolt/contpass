<?php

namespace App\Filament\Resources\IncomeRecords\Pages;

use App\Filament\Resources\IncomeRecords\IncomeRecordResource;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\PostIncomeVoucher;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateIncomeRecord extends CreateRecord
{
    protected static string $resource = IncomeRecordResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $thirdParty = ThirdParty::query()->findOrFail((int) $data['third_party_id']);

        $voucher = app(PostIncomeVoucher::class)->handle(app(CurrentCompany::class)->get(), $thirdParty, $data);

        return $voucher->incomeRecord()->firstOrFail();
    }
}

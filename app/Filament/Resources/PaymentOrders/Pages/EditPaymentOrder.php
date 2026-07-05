<?php

namespace App\Filament\Resources\PaymentOrders\Pages;

use App\Filament\Resources\PaymentOrders\PaymentOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditPaymentOrder extends EditRecord
{
    protected static string $resource = PaymentOrderResource::class;
}

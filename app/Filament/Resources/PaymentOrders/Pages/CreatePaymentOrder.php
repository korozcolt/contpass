<?php

namespace App\Filament\Resources\PaymentOrders\Pages;

use App\Filament\Resources\PaymentOrders\PaymentOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentOrder extends CreateRecord
{
    protected static string $resource = PaymentOrderResource::class;
}

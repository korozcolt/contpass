<?php

namespace App\Services\Budget;

use App\Enums\PaymentOrderStatus;
use App\Models\Company;
use App\Models\PaymentOrder;
use App\Services\Accounting\RegisterPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecutePaymentOrder
{
    public function __construct(
        private readonly RegisterPayment $registerPayment,
    ) {}

    /**
     * Ejecuta una Orden de Pago aprobada.
     * Invoca RegisterPayment para generar el comprobante contable de pago
     * y marca la OP como pagada con la fecha real.
     */
    public function handle(
        Company $company,
        PaymentOrder $order,
        string $paidOn,
        ?string $reference = null
    ): PaymentOrder {
        if ($order->status !== PaymentOrderStatus::Approved) {
            throw ValidationException::withMessages([
                'order' => 'Solo las órdenes de pago aprobadas pueden ser ejecutadas.',
            ]);
        }

        $order->load(['budgetObligation.budgetRegistration.thirdParty', 'cashAccount']);
        $sourceVoucher = $order->budgetObligation->voucher;
        $cashAccount = $order->cashAccount;

        return DB::transaction(function () use ($company, $order, $cashAccount, $sourceVoucher, $paidOn, $reference): PaymentOrder {
            $paymentVoucher = $this->registerPayment->handle($company, $cashAccount, [
                'cash_account_id' => $cashAccount->id,
                'counterparty_account_id' => $sourceVoucher->entries()
                    ->whereRaw('credit > 0')
                    ->orderBy('id')
                    ->value('chart_account_id'),
                'method' => $order->method->value,
                'reference' => $reference ?? $order->reference,
                'paid_on' => $paidOn,
                'amount' => (float) $order->amount,
                'description' => $order->description,
            ], $sourceVoucher);

            $order->forceFill([
                'status' => PaymentOrderStatus::Paid,
                'voucher_id' => $paymentVoucher->id,
                'paid_on' => $paidOn,
                'reference' => $reference ?? $order->reference,
            ])->save();

            return $order->refresh();
        });
    }
}

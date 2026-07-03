<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;
use App\Models\CashAccount;
use App\Models\ChartAccount;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\RegisterPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('payments.index', [
            'payments' => Payment::query()
                ->with(['voucher.thirdParty', 'cashAccount'])
                ->whereHas('voucher', fn ($query) => $query->whereBelongsTo($this->currentCompany->get()))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('payments.form', $this->formData());
    }

    public function store(StorePaymentRequest $request, RegisterPayment $registerPayment): RedirectResponse
    {
        $cashAccount = CashAccount::query()->findOrFail($request->integer('cash_account_id'));
        $sourceVoucher = $request->filled('source_voucher_id') ? Voucher::query()->with('thirdParty')->findOrFail($request->integer('source_voucher_id')) : null;
        $voucher = $registerPayment->handle($this->currentCompany->get(), $cashAccount, $request->validated(), $sourceVoucher);

        return redirect()->route('payments.show', $voucher->payment)->with('status', "Pago registrado en {$voucher->number}.");
    }

    public function show(Payment $payment): View
    {
        return view('payments.show', ['payment' => $payment->load('voucher.entries.chartAccount', 'voucher.thirdParty', 'cashAccount')]);
    }

    public function edit(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.show', $payment)->with('status', 'Los pagos aprobados se corrigen con nota de ajuste.');
    }

    public function update(StorePaymentRequest $request, Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.show', $payment)->with('status', 'Los pagos aprobados se corrigen con nota de ajuste.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.show', $payment)->with('status', 'Los pagos aprobados no se eliminan directamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $company = $this->currentCompany->get();

        return [
            'cashAccounts' => CashAccount::query()->whereBelongsTo($company)->orderBy('name')->get(),
            'chartAccounts' => ChartAccount::query()->whereBelongsTo($company)->orderBy('code')->get(),
            'vouchers' => Voucher::query()->with('thirdParty')->whereBelongsTo($company)->latest()->limit(100)->get(),
            'methods' => PaymentMethod::cases(),
        ];
    }
}

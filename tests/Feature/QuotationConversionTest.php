<?php

use App\Enums\AccountNature;
use App\Enums\QuotationStatus;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\ThirdParty;
use App\Models\Voucher;
use App\Services\Accounting\ConvertQuotationToIncome;
use Illuminate\Validation\ValidationException;

function quotationFixture(array $attributes = []): Quotation
{
    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id]);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595', 'nature' => AccountNature::Credit]);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505', 'nature' => AccountNature::Debit]);

    $quotation = Quotation::factory()->for($company)->create(array_merge([
        'third_party_id' => $thirdParty->id,
        'revenue_account_id' => $revenue->id,
        'receivable_account_id' => $receivable->id,
        'status' => QuotationStatus::Accepted,
        'voucher_id' => null,
    ], $attributes));

    QuotationLine::factory()->for($quotation)->create(['quantity' => 2, 'unit_price' => 10000]);
    QuotationLine::factory()->for($quotation)->create(['quantity' => 3, 'unit_price' => 5000]);

    return $quotation;
}

it('converts an accepted quotation into a voucher and marks it converted', function () {
    $quotation = quotationFixture();

    $voucher = app(ConvertQuotationToIncome::class)->handle($quotation);

    expect(Voucher::count())->toBe(1)
        ->and($voucher->incomeRecord)->not->toBeNull()
        ->and((float) $voucher->incomeRecord->amount)->toBe($quotation->total)
        ->and($quotation->refresh()->status)->toBe(QuotationStatus::Converted)
        ->and($quotation->voucher_id)->toBe($voucher->id);
});

it('rejects converting an already converted quotation and does not create a second voucher', function () {
    $quotation = quotationFixture();
    app(ConvertQuotationToIncome::class)->handle($quotation);
    $voucherCountAfterFirstConversion = Voucher::count();

    expect(fn () => app(ConvertQuotationToIncome::class)->handle($quotation->refresh()))
        ->toThrow(ValidationException::class, 'Esta cotización ya fue convertida o no está Aceptada.');

    expect(Voucher::count())->toBe($voucherCountAfterFirstConversion);
});

it('rejects converting a quotation that was never accepted without creating any voucher', function () {
    $quotation = quotationFixture(['status' => QuotationStatus::Draft]);

    expect(fn () => app(ConvertQuotationToIncome::class)->handle($quotation))
        ->toThrow(ValidationException::class, 'Esta cotización ya fue convertida o no está Aceptada.');

    expect(Voucher::count())->toBe(0);
});

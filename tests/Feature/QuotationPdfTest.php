<?php

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\ThirdParty;
use App\Models\User;

function quotationPdfFixture(): Quotation
{
    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id]);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595']);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505']);

    $quotation = Quotation::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $thirdParty->id,
        'revenue_account_id' => $revenue->id,
        'receivable_account_id' => $receivable->id,
    ]);
    $quotation->lines()->create(['description' => 'Servicio', 'quantity' => 2, 'unit_price' => 50000]);

    return $quotation;
}

it('downloads the quotation pdf for an authenticated user', function () {
    $this->actingAs(User::factory()->create());
    $quotation = quotationPdfFixture();

    $this->get(route('quotations.pdf', $quotation))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('renders the pdf without a notes section when notes are blank', function () {
    $this->actingAs(User::factory()->create());
    $quotation = quotationPdfFixture();

    expect($quotation->notes)->toBeNull();

    $this->get(route('quotations.pdf', $quotation))->assertSuccessful();
});

it('blocks the quotation pdf download for guests', function () {
    $quotation = quotationPdfFixture();

    $this->get(route('quotations.pdf', $quotation))->assertForbidden();
});

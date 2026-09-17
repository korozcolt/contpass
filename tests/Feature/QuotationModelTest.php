<?php

use App\Enums\QuotationStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->company = Company::factory()->create();
    $this->actingAs($this->admin);
});

it('sums quotation lines into the total accessor', function () {
    $quotation = Quotation::factory()->for($this->company)->create();

    QuotationLine::factory()->for($quotation)->create(['quantity' => 2, 'unit_price' => 10000]);
    QuotationLine::factory()->for($quotation)->create(['quantity' => 3, 'unit_price' => 5000]);

    expect($quotation->total)->toBe(35000.0);
});

it('auto-calculates the line subtotal on save', function () {
    $quotation = Quotation::factory()->for($this->company)->create();

    $line = QuotationLine::factory()->for($quotation)->create([
        'quantity' => 2,
        'unit_price' => 10000,
        'subtotal' => 0,
    ]);

    expect((float) $line->subtotal)->toBe(20000.0);
});

it('reports Expired only when Sent and past its expiry date, never for Draft', function () {
    $sentExpired = Quotation::factory()->for($this->company)->create([
        'status' => QuotationStatus::Sent,
        'expires_on' => now()->subDay(),
    ]);
    $sentFuture = Quotation::factory()->for($this->company)->create([
        'status' => QuotationStatus::Sent,
        'expires_on' => now()->addDay(),
    ]);
    $draftExpired = Quotation::factory()->for($this->company)->create([
        'status' => QuotationStatus::Draft,
        'expires_on' => now()->subDay(),
    ]);

    expect($sentExpired->effectiveStatus())->toBe(QuotationStatus::Expired)
        ->and($sentFuture->effectiveStatus())->toBe(QuotationStatus::Sent)
        ->and($draftExpired->effectiveStatus())->toBe(QuotationStatus::Draft);
});

it('is read-only only when Converted', function () {
    foreach (QuotationStatus::cases() as $status) {
        $quotation = Quotation::factory()->for($this->company)->create(['status' => $status]);

        expect($quotation->isReadOnly())->toBe($status === QuotationStatus::Converted);
    }
});

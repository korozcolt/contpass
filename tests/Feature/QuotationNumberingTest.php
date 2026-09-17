<?php

use App\Models\Company;
use App\Models\Quotation;
use App\Services\Accounting\BuildQuotationNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 00001 for a company with no prior quotations this year', function () {
    $company = Company::factory()->create();

    $number = app(BuildQuotationNumber::class)->next($company);

    expect($number)->toBe('COT-'.now()->format('Y').'-00001');
});

it('increments to 00002 after a quotation with the generated number exists', function () {
    $company = Company::factory()->create();

    $first = app(BuildQuotationNumber::class)->next($company);
    Quotation::factory()->for($company)->create(['number' => $first]);

    $second = app(BuildQuotationNumber::class)->next($company);

    expect($second)->toBe('COT-'.now()->format('Y').'-00002');
});

it('scopes numbering independently per company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $numberA = app(BuildQuotationNumber::class)->next($companyA);
    $numberB = app(BuildQuotationNumber::class)->next($companyB);

    expect($numberA)->toBe('COT-'.now()->format('Y').'-00001')
        ->and($numberB)->toBe('COT-'.now()->format('Y').'-00001');
});

it('ignores quotations from a previous year when counting the current year', function () {
    $company = Company::factory()->create();
    $previousYear = now()->subYear()->format('Y');
    Quotation::factory()->for($company)->create(['number' => "COT-{$previousYear}-00099"]);

    $number = app(BuildQuotationNumber::class)->next($company);

    expect($number)->toBe('COT-'.now()->format('Y').'-00001');
});

it('relies on the composite unique index to reject duplicate numbers within the same company', function () {
    $company = Company::factory()->create();
    $number = 'COT-'.now()->format('Y').'-00001';

    Quotation::factory()->for($company)->create(['number' => $number]);

    expect(fn () => Quotation::factory()->for($company)->create(['number' => $number]))
        ->toThrow(UniqueConstraintViolationException::class);
});

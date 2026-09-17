<?php

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Filament\Resources\Quotations\Pages\ListQuotations;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\ThirdParty;
use App\Models\User;
use App\Services\Accounting\BuildQuotationNumber;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

function quotationLifecycleFixture(): Quotation
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
        'status' => QuotationStatus::Draft,
    ]);
    $quotation->lines()->create(['description' => 'Servicio', 'quantity' => 1, 'unit_price' => 100000]);

    return $quotation;
}

it('moves a quotation through the full lifecycle and converts it to income exactly once', function () {
    $this->actingAs(User::factory()->create());
    $quotation = quotationLifecycleFixture();

    $component = Livewire::test(ListQuotations::class);

    $component->callAction(TestAction::make('send')->table($quotation));
    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent);

    $component->callAction(TestAction::make('accept')->table($quotation));
    expect($quotation->fresh()->status)->toBe(QuotationStatus::Accepted);

    $component->callAction(TestAction::make('convert')->table($quotation));

    $converted = $quotation->fresh();
    expect($converted->status)->toBe(QuotationStatus::Converted)
        ->and($converted->voucher_id)->not->toBeNull()
        ->and($converted->voucher->incomeRecord->amount)->toEqual('100000.00');

    $this->get(EditQuotation::getUrl(['record' => $converted]))->assertForbidden();
});

it('requires a rejection reason to reject a quotation', function () {
    $this->actingAs(User::factory()->create());
    $quotation = quotationLifecycleFixture();
    $quotation->forceFill(['status' => QuotationStatus::Sent])->save();

    Livewire::test(ListQuotations::class)
        ->callAction(TestAction::make('reject')->table($quotation), ['rejection_reason' => ''])
        ->assertHasFormErrors(['rejection_reason' => 'required']);

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent);
});

it('shows the UI-SPEC friendly message when the quotation number collides at insert time', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create();
    $thirdParty = ThirdParty::factory()->create(['company_id' => $company->id]);
    $revenue = ChartAccount::factory()->credit()->create(['company_id' => $company->id, 'code' => '413595']);
    $receivable = ChartAccount::factory()->create(['company_id' => $company->id, 'code' => '130505']);

    // Fuerza la colisión: una Quotation YA existe con el número que
    // BuildQuotationNumber::next() va a devolver de nuevo, porque
    // mockeamos el servicio para simular la ventana de carrera descrita
    // en 01-02-PLAN.md (lockForUpdate() no bloquea nada cuando count=0).
    $collidingNumber = 'COT-'.now()->format('Y').'-00001';

    Quotation::factory()->create([
        'company_id' => $company->id,
        'third_party_id' => $thirdParty->id,
        'revenue_account_id' => $revenue->id,
        'receivable_account_id' => $receivable->id,
        'number' => $collidingNumber,
    ]);

    $this->mock(BuildQuotationNumber::class, function ($mock) use ($collidingNumber): void {
        $mock->shouldReceive('next')->once()->andReturn($collidingNumber);
    });

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'company_id' => $company->id,
            'third_party_id' => $thirdParty->id,
            'revenue_account_id' => $revenue->id,
            'receivable_account_id' => $receivable->id,
            'expires_on' => now()->addDays(30)->toDateString(),
            'lines' => [
                ['description' => 'Servicio', 'quantity' => 1, 'unit_price' => 100000],
            ],
        ])
        ->call('create')
        ->assertHasErrors(['number']);
});

<?php

use App\Filament\Pages\AccountsPayableReport;
use App\Filament\Pages\AccountsReceivableReport;
use App\Filament\Pages\GeneralLedgerReport;
use App\Filament\Pages\LedgerReport;
use App\Filament\Pages\ThirdPartyMovementsReport;
use App\Filament\Pages\TrialBalanceReport;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

it('shows an Exportar Excel action linking to the ledger .xlsx route', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(LedgerReport::class)
        ->assertTableActionExists('exportExcel')
        ->assertTableActionHasUrl('exportExcel', route('accounting-reports.ledger.xlsx'));
});

it('shows an Exportar Excel action linking to the third-party-movements .xlsx route', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ThirdPartyMovementsReport::class)
        ->assertTableActionExists('exportExcel')
        ->assertTableActionHasUrl('exportExcel', route('accounting-reports.third-party-movements.xlsx'));
});

it('shows an Exportar Excel action linking to the trial-balance .xlsx route', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(TrialBalanceReport::class)
        ->assertTableActionExists('exportExcel')
        ->assertTableActionHasUrl('exportExcel', route('accounting-reports.trial-balance.xlsx'));
});

it('shows an Exportar Excel action linking to the general-ledger .xlsx route', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(GeneralLedgerReport::class)
        ->assertTableActionExists('exportExcel')
        ->assertTableActionHasUrl('exportExcel', route('accounting-reports.general-ledger.xlsx'));
});

it('shows an Exportar Excel action linking to the accounts-receivable .xlsx route', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(AccountsReceivableReport::class)
        ->assertTableActionExists('exportExcel')
        ->assertTableActionHasUrl('exportExcel', route('accounting-reports.accounts-receivable.xlsx'));
});

it('shows an Exportar Excel action linking to the accounts-payable .xlsx route', function () {
    Company::factory()->create(['has_budgetary_control' => true]);
    $this->actingAs(User::factory()->create());

    Livewire::test(AccountsPayableReport::class)
        ->assertTableActionExists('exportExcel')
        ->assertTableActionHasUrl('exportExcel', route('accounting-reports.accounts-payable.xlsx'));
});

<?php

use App\Enums\WarehouseItemType;
use App\Enums\WarehouseMovementType;
use App\Filament\Pages\WarehouseItemLedgerReport;
use App\Filament\Pages\WarehouseStockReport;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Livewire\Livewire;

it('shows the current stock matching the item accessor', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $item = WarehouseItem::factory()->create(['company_id' => $company->id, 'type' => WarehouseItemType::Consumable]);

    $entry = WarehouseMovement::query()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'type' => WarehouseMovementType::Entry,
        'date' => now()->toDateString(),
        'description' => 'Entrada inicial',
    ]);
    $entry->lines()->create(['warehouse_item_id' => $item->id, 'quantity' => 25]);

    Livewire::test(WarehouseStockReport::class)->assertSuccessful();

    expect($item->fresh()->current_stock)->toBe(25.0);
});

it('renders the warehouse item ledger report', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $item = WarehouseItem::factory()->create(['company_id' => $company->id]);

    $entry = WarehouseMovement::query()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'type' => WarehouseMovementType::Entry,
        'date' => now()->toDateString(),
        'description' => 'Entrada inicial',
    ]);
    $entry->lines()->create(['warehouse_item_id' => $item->id, 'quantity' => 15]);

    Livewire::test(WarehouseItemLedgerReport::class)->assertSuccessful();
});

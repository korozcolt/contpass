<?php

use App\Enums\WarehouseItemType;
use App\Enums\WarehouseMovementType;
use App\Filament\Resources\WarehouseMovements\Pages\CreateWarehouseMovement;
use App\Filament\Resources\WarehouseMovements\Pages\EditWarehouseMovement;
use App\Filament\Resources\WarehouseMovements\RelationManagers\LinesRelationManager;
use App\Models\Company;
use App\Models\Dependency;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

function warehouseFixture(): array
{
    $company = Company::factory()->create();
    $warehouseA = Warehouse::factory()->create(['company_id' => $company->id]);
    $warehouseB = Warehouse::factory()->create(['company_id' => $company->id]);
    $item = WarehouseItem::factory()->create(['company_id' => $company->id, 'type' => WarehouseItemType::Consumable]);
    $dependency = Dependency::factory()->create(['company_id' => $company->id]);

    return compact('company', 'warehouseA', 'warehouseB', 'item', 'dependency');
}

it('increases stock with an entry movement', function () {
    $data = warehouseFixture();

    $entry = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'type' => WarehouseMovementType::Entry,
        'date' => now()->toDateString(),
        'description' => 'Entrada inicial',
    ]);
    $entry->lines()->create(['warehouse_item_id' => $data['item']->id, 'quantity' => 10]);

    expect($data['item']->fresh()->current_stock)->toBe(10.0)
        ->and($entry->number)->toStartWith('ENT-');
});

it('decreases stock with an exit movement within available balance', function () {
    $data = warehouseFixture();

    $entry = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'type' => WarehouseMovementType::Entry,
        'date' => now()->toDateString(),
        'description' => 'Entrada inicial',
    ]);
    $entry->lines()->create(['warehouse_item_id' => $data['item']->id, 'quantity' => 10]);

    $exit = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'dependency_id' => $data['dependency']->id,
        'type' => WarehouseMovementType::Exit,
        'date' => now()->toDateString(),
        'description' => 'Salida a dependencia',
    ]);
    $exit->lines()->create(['warehouse_item_id' => $data['item']->id, 'quantity' => 4]);

    expect($data['item']->fresh()->current_stock)->toBe(6.0)
        ->and($exit->number)->toStartWith('EXI-');
});

it('does not change total company stock on a transfer between warehouses', function () {
    $data = warehouseFixture();

    $entry = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'type' => WarehouseMovementType::Entry,
        'date' => now()->toDateString(),
        'description' => 'Entrada inicial',
    ]);
    $entry->lines()->create(['warehouse_item_id' => $data['item']->id, 'quantity' => 10]);

    $transfer = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'destination_warehouse_id' => $data['warehouseB']->id,
        'type' => WarehouseMovementType::Transfer,
        'date' => now()->toDateString(),
        'description' => 'Traslado entre almacenes',
    ]);
    $transfer->lines()->create(['warehouse_item_id' => $data['item']->id, 'quantity' => 5]);

    expect($data['item']->fresh()->current_stock)->toBe(10.0);
});

it('rejects an exit line quantity greater than the available stock', function () {
    $this->actingAs(User::factory()->create());
    $data = warehouseFixture();

    $entry = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'type' => WarehouseMovementType::Entry,
        'date' => now()->toDateString(),
        'description' => 'Entrada inicial',
    ]);
    $entry->lines()->create(['warehouse_item_id' => $data['item']->id, 'quantity' => 10]);

    $exit = WarehouseMovement::query()->create([
        'company_id' => $data['company']->id,
        'warehouse_id' => $data['warehouseA']->id,
        'dependency_id' => $data['dependency']->id,
        'type' => WarehouseMovementType::Exit,
        'date' => now()->toDateString(),
        'description' => 'Salida excesiva',
    ]);

    Livewire::test(LinesRelationManager::class, [
        'ownerRecord' => $exit,
        'pageClass' => EditWarehouseMovement::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'warehouse_item_id' => $data['item']->id,
            'quantity' => 999,
        ])
        ->assertHasFormErrors(['quantity' => 'max']);
});

it('shows the type-specific fields on the movement form based on the selected type', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateWarehouseMovement::class)
        ->assertFormFieldHidden('destination_warehouse_id')
        ->assertFormFieldHidden('dependency_id')
        ->assertFormFieldHidden('third_party_id')
        ->fillForm(['type' => WarehouseMovementType::Transfer->value])
        ->assertFormFieldVisible('destination_warehouse_id')
        ->fillForm(['type' => WarehouseMovementType::Exit->value])
        ->assertFormFieldVisible('dependency_id')
        ->fillForm(['type' => WarehouseMovementType::Entry->value])
        ->assertFormFieldVisible('third_party_id');
});

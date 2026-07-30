<?php

namespace Database\Factories;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Models\WarehouseMovementLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseMovementLine>
 */
class WarehouseMovementLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_movement_id' => WarehouseMovement::factory(),
            'warehouse_item_id' => WarehouseItem::factory(),
            'quantity' => fake()->randomFloat(2, 1, 50),
            'unit_cost' => fake()->randomFloat(2, 1000, 100000),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\WarehouseMovementType;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseMovement>
 */
class WarehouseMovementFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'warehouse_id' => Warehouse::factory()->state(['company_id' => $company]),
            'type' => WarehouseMovementType::Entry,
            'date' => now()->toDateString(),
            'description' => fake()->sentence(),
        ];
    }
}

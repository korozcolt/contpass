<?php

namespace Database\Factories;

use App\Enums\WarehouseItemType;
use App\Models\Company;
use App\Models\WarehouseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseItem>
 */
class WarehouseItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => fake()->unique()->numerify('EL-####'),
            'name' => fake()->words(3, true),
            'type' => WarehouseItemType::Consumable,
            'unit_of_measure' => 'Unidad',
            'minimum_stock' => 10,
            'is_active' => true,
        ];
    }

    public function returnable(): static
    {
        return $this->state(fn (): array => ['type' => WarehouseItemType::Returnable, 'minimum_stock' => null]);
    }
}

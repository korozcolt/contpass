<?php

namespace App\Services\Warehouse;

use App\Enums\WarehouseMovementType;
use App\Models\WarehouseMovement;

class BuildWarehouseMovementNumber
{
    public function next(WarehouseMovementType $type): string
    {
        $prefix = strtoupper(substr($type->value, 0, 3));
        $next = WarehouseMovement::query()->where('type', $type->value)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $next);
    }
}

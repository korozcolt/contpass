<?php

namespace App\Models;

use Database\Factories\WarehouseMovementLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMovementLine extends Model
{
    /** @use HasFactory<WarehouseMovementLineFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_movement_id',
        'warehouse_item_id',
        'quantity',
        'unit_cost',
        'asset_tag',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function warehouseMovement(): BelongsTo
    {
        return $this->belongsTo(WarehouseMovement::class);
    }

    public function warehouseItem(): BelongsTo
    {
        return $this->belongsTo(WarehouseItem::class);
    }
}

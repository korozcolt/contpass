<?php

namespace App\Models;

use App\Enums\WarehouseItemType;
use Database\Factories\WarehouseItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseItem extends Model
{
    /** @use HasFactory<WarehouseItemFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'unit_of_measure',
        'minimum_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => WarehouseItemType::class,
            'minimum_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function movementLines(): HasMany
    {
        return $this->hasMany(WarehouseMovementLine::class);
    }

    protected function currentStock(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $lines = $this->movementLines()->with('warehouseMovement')->get();

                return $lines->reduce(function (float $stock, WarehouseMovementLine $line): float {
                    $type = $line->warehouseMovement->type;

                    return match (true) {
                        $type->increasesStock() => $stock + (float) $line->quantity,
                        $type->decreasesStock() => $stock - (float) $line->quantity,
                        default => $stock,
                    };
                }, 0.0);
            }
        );
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}

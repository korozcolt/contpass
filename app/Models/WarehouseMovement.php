<?php

namespace App\Models;

use App\Enums\WarehouseMovementType;
use App\Services\Warehouse\BuildWarehouseMovementNumber;
use Database\Factories\WarehouseMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseMovement extends Model
{
    /** @use HasFactory<WarehouseMovementFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (WarehouseMovement $movement): void {
            if (blank($movement->number)) {
                $movement->number = app(BuildWarehouseMovementNumber::class)->next($movement->type);
            }
        });
    }

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'destination_warehouse_id',
        'dependency_id',
        'third_party_id',
        'type',
        'number',
        'date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => WarehouseMovementType::class,
            'date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function dependency(): BelongsTo
    {
        return $this->belongsTo(Dependency::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseMovementLine::class);
    }
}

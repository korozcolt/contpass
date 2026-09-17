<?php

namespace App\Models;

use Database\Factories\QuotationLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationLine extends Model
{
    /** @use HasFactory<QuotationLineFactory> */
    use HasFactory;

    protected $fillable = ['quotation_id', 'description', 'quantity', 'unit_price', 'subtotal'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $line->subtotal = round((float) $line->quantity * (float) $line->unit_price, 2);
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}

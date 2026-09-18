<?php

namespace App\Models;

use Database\Factories\ExternalInvoiceReferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referencia manual de una factura electrónica emitida por un proveedor tercero.
 * Sin integración activa (INVHOOK-03) — no realiza llamadas HTTP; ver INVHOOK-04 (v2) para integración futura.
 */
class ExternalInvoiceReference extends Model
{
    /** @use HasFactory<ExternalInvoiceReferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'income_record_id',
        'invoice_number',
        'cufe',
        'provider',
        'document_url',
    ];

    public function incomeRecord(): BelongsTo
    {
        return $this->belongsTo(IncomeRecord::class);
    }
}

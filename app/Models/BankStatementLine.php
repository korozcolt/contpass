<?php

namespace App\Models;

use App\Enums\BankStatementLineStatus;
use Database\Factories\BankStatementLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementLine extends Model
{
    /** @use HasFactory<BankStatementLineFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_statement_import_id',
        'line_date',
        'description',
        'amount',
        'reference',
        'raw_row',
        'status',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'line_date' => 'date',
            'amount' => 'decimal:2',
            'raw_row' => 'array',
            'status' => BankStatementLineStatus::class,
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankStatementMatch::class, 'bank_statement_line_id');
    }
}

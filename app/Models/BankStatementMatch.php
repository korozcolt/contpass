<?php

namespace App\Models;

use App\Enums\BankStatementMatchConfidence;
use App\Enums\BankStatementMatchStatus;
use Database\Factories\BankStatementMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BankStatementMatch extends Model
{
    /** @use HasFactory<BankStatementMatchFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_statement_line_id',
        'confidence',
        'status',
        'confirmed_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => BankStatementMatchConfidence::class,
            'status' => BankStatementMatchStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id');
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'bank_statement_match_payment');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}

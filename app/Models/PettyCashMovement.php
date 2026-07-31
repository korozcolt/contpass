<?php

namespace App\Models;

use App\Enums\PettyCashMovementType;
use App\Traits\Auditable;
use Database\Factories\PettyCashMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashMovement extends Model
{
    /** @use HasFactory<PettyCashMovementFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'petty_cash_fund_id',
        'third_party_id',
        'type',
        'date',
        'amount',
        'support_number',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => PettyCashMovementType::class,
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function pettyCashFund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }
}

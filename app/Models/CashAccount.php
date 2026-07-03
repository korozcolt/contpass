<?php

namespace App\Models;

use App\Enums\CashAccountType;
use Database\Factories\CashAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAccount extends Model
{
    /** @use HasFactory<CashAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'chart_account_id',
        'type',
        'name',
        'bank_name',
        'account_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }
}

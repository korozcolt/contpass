<?php

namespace App\Models;

use App\Enums\PayrollFundType;
use Database\Factories\PayrollFundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollFund extends Model
{
    /** @use HasFactory<PayrollFundFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'nit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayrollFundType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

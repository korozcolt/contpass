<?php

namespace App\Models;

use App\Enums\PayrollConceptType;
use Database\Factories\PayrollConceptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollConcept extends Model
{
    /** @use HasFactory<PayrollConceptFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayrollConceptType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

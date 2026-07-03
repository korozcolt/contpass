<?php

namespace App\Models;

use App\Enums\AccountNature;
use Database\Factories\ChartAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartAccount extends Model
{
    /** @use HasFactory<ChartAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'nature',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nature' => AccountNature::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}

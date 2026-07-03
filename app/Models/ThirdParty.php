<?php

namespace App\Models;

use App\Enums\ThirdPartyType;
use Database\Factories\ThirdPartyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirdParty extends Model
{
    /** @use HasFactory<ThirdPartyFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'tax_id',
        'verification_digit',
        'email',
        'phone',
        'city',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'type' => ThirdPartyType::class,
            'verification_digit' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

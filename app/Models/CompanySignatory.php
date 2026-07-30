<?php

namespace App\Models;

use App\Enums\SignatoryArea;
use Database\Factories\CompanySignatoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySignatory extends Model
{
    /** @use HasFactory<CompanySignatoryFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'area',
        'full_name',
        'position',
        'identification',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'area' => SignatoryArea::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

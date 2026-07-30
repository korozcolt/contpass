<?php

namespace App\Models;

use App\Enums\CompanyType;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_id',
        'verification_digit',
        'currency',
        'has_budgetary_control',
        'type',
        'phone',
        'email',
        'address',
        'city',
        'legal_representative',
        'dane_department_code',
        'dane_municipality_code',
    ];

    protected $attributes = [
        'has_budgetary_control' => false,
        'type' => 'private',
    ];

    protected function casts(): array
    {
        return [
            'has_budgetary_control' => 'boolean',
            'type' => CompanyType::class,
        ];
    }

    public function thirdParties(): HasMany
    {
        return $this->hasMany(ThirdParty::class);
    }

    public function chartAccounts(): HasMany
    {
        return $this->hasMany(ChartAccount::class);
    }

    public function cashAccounts(): HasMany
    {
        return $this->hasMany(CashAccount::class);
    }

    public function withholdingRules(): HasMany
    {
        return $this->hasMany(WithholdingRule::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(CompanySignatory::class);
    }
}

<?php

namespace App\Models;

use App\Enums\EmployeeContractType;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'dependency_id',
        'pension_fund_id',
        'health_fund_id',
        'tax_id',
        'verification_digit',
        'name',
        'position',
        'contract_type',
        'hire_date',
        'termination_date',
        'base_salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'contract_type' => EmployeeContractType::class,
            'verification_digit' => 'integer',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'base_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function dependency(): BelongsTo
    {
        return $this->belongsTo(Dependency::class);
    }

    public function pensionFund(): BelongsTo
    {
        return $this->belongsTo(PayrollFund::class, 'pension_fund_id');
    }

    public function healthFund(): BelongsTo
    {
        return $this->belongsTo(PayrollFund::class, 'health_fund_id');
    }
}

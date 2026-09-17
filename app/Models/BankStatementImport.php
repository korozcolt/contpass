<?php

namespace App\Models;

use App\Enums\BankProfile;
use Database\Factories\BankStatementImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    /** @use HasFactory<BankStatementImportFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cash_account_id',
        'bank',
        'file_name',
        'starts_on',
        'ends_on',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'bank' => BankProfile::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

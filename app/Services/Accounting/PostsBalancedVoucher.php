<?php

namespace App\Services\Accounting;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\ThirdParty;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostsBalancedVoucher
{
    public function __construct(
        private readonly BuildVoucherNumber $buildVoucherNumber,
        private readonly EnsureOpenAccountingPeriod $ensureOpenAccountingPeriod,
    ) {}

    /**
     * @param  array<int, array{chart_account_id: int, third_party_id?: int|null, description: string, debit?: float, credit?: float}>  $entries
     */
    public function handle(Company $company, VoucherType $type, string $date, string $description, array $entries, ?ThirdParty $thirdParty = null, ?Voucher $adjustsVoucher = null): Voucher
    {
        $this->ensureOpenAccountingPeriod->handle($company, $date);

        if (! Voucher::entriesAreBalanced($entries)) {
            throw ValidationException::withMessages([
                'entries' => 'El comprobante debe tener débitos y créditos balanceados.',
            ]);
        }

        return DB::transaction(function () use ($company, $type, $date, $description, $entries, $thirdParty, $adjustsVoucher): Voucher {
            $voucher = Voucher::query()->create([
                'company_id' => $company->id,
                'third_party_id' => $thirdParty?->id,
                'adjusts_voucher_id' => $adjustsVoucher?->id,
                'type' => $type,
                'status' => VoucherStatus::Approved,
                'number' => $this->buildVoucherNumber->next($type),
                'date' => $date,
                'description' => $description,
                'approved_at' => now(),
            ]);

            foreach ($entries as $entry) {
                $voucher->entries()->create([
                    'chart_account_id' => $entry['chart_account_id'],
                    'third_party_id' => $entry['third_party_id'] ?? $thirdParty?->id,
                    'description' => $entry['description'],
                    'debit' => $entry['debit'] ?? 0,
                    'credit' => $entry['credit'] ?? 0,
                ]);
            }

            return $voucher->load('entries.chartAccount', 'thirdParty');
        });
    }
}

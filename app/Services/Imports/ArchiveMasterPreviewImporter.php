<?php

namespace App\Services\Imports;

use App\Enums\AccountNature;
use App\Enums\CashAccountType;
use App\Enums\ThirdPartyType;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\ExpenseRecord;
use App\Models\ThirdParty;
use App\Models\Voucher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

class ArchiveMasterPreviewImporter
{
    /**
     * @return array{
     *     dry_run: bool,
     *     company: string,
     *     chart_accounts: int,
     *     third_parties: int,
     *     cash_accounts: int,
     *     periods: int,
     *     vouchers_imported: int,
     *     vouchers_skipped: int,
     *     vouchers_rejected: int,
     *     rejected: array<int, array{id: string|null, number: string|null, reason: string}>
     * }
     *
     * @throws JsonException
     */
    public function import(string $path, bool $dryRun = true): array
    {
        $payload = json_decode(file_get_contents($path) ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        DB::beginTransaction();

        try {
            $summary = [
                'dry_run' => $dryRun,
                'company' => '',
                'chart_accounts' => 0,
                'third_parties' => 0,
                'cash_accounts' => 0,
                'periods' => 0,
                'vouchers_imported' => 0,
                'vouchers_skipped' => 0,
                'vouchers_rejected' => 0,
                'rejected' => [],
            ];

            $company = $this->upsertCompany($payload);
            $summary['company'] = $company->name;
            $summary['chart_accounts'] = $this->upsertChartAccounts($company, $payload);
            $summary['third_parties'] = $this->upsertThirdParties($company, $payload);
            $summary['cash_accounts'] = $this->upsertCashAccounts($company);
            $summary['periods'] = $this->upsertPeriods($company, $payload);

            foreach ($payload['vouchers'] ?? [] as $voucherPayload) {
                $result = $this->importVoucher($company, $voucherPayload);

                if ($result === 'imported') {
                    $summary['vouchers_imported']++;

                    continue;
                }

                if ($result === 'skipped') {
                    $summary['vouchers_skipped']++;

                    continue;
                }

                $summary['vouchers_rejected']++;
                $summary['rejected'][] = [
                    'id' => $voucherPayload['id'] ?? null,
                    'number' => $voucherPayload['number'] ?? null,
                    'reason' => $result,
                ];
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return $summary;
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertCompany(array $payload): Company
    {
        $companyPayload = $payload['company'] ?? [];
        $taxId = (string) ($companyPayload['tax_id'] ?? $this->inferCompanyTaxId($payload));

        return Company::query()->updateOrCreate(
            ['tax_id' => $taxId],
            [
                'name' => $companyPayload['name'] ?? 'Empresa importada Archive Master',
                'verification_digit' => $companyPayload['verification_digit'] ?? null,
                'currency' => $payload['currency'] ?? 'COP',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function inferCompanyTaxId(array $payload): string
    {
        $companyName = mb_strtoupper((string) Arr::get($payload, 'company.name', ''));

        foreach ($payload['third_parties'] ?? [] as $thirdParty) {
            if (mb_strtoupper((string) ($thirdParty['name'] ?? '')) === $companyName) {
                return (string) $thirdParty['id_number'];
            }
        }

        return 'ARCHIVE-MASTER-'.sha1($companyName ?: json_encode($payload['company'] ?? []));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertChartAccounts(Company $company, array $payload): int
    {
        $codes = collect($payload['chart_accounts'] ?? [])
            ->mapWithKeys(fn (array $account): array => [(string) $account['code'] => (string) ($account['name'] ?? $account['code'])]);

        foreach ($payload['vouchers'] ?? [] as $voucher) {
            foreach ($voucher['lines'] ?? [] as $line) {
                $code = (string) $line['account_code'];
                $codes[$code] ??= (string) ($line['description'] ?? $code);
            }
        }

        foreach ($codes as $code => $name) {
            ChartAccount::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                [
                    'name' => $name,
                    'nature' => $this->inferAccountNature($code),
                    'is_active' => true,
                ],
            );
        }

        return $codes->count();
    }

    private function inferAccountNature(string $code): AccountNature
    {
        return match (mb_substr($code, 0, 1)) {
            '2', '3', '4' => AccountNature::Credit,
            default => AccountNature::Debit,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertThirdParties(Company $company, array $payload): int
    {
        $thirdParties = collect($payload['third_parties'] ?? [])
            ->mapWithKeys(fn (array $thirdParty): array => [(string) $thirdParty['id_number'] => $thirdParty]);

        foreach ($payload['vouchers'] ?? [] as $voucher) {
            $taxId = (string) ($voucher['third_party_id_number'] ?? '');

            if ($taxId !== '' && (! $thirdParties->has($taxId))) {
                $thirdParties[$taxId] = [
                    'id_number' => $taxId,
                    'name' => $voucher['third_party_name'] ?? $taxId,
                    'legal_type' => 'unknown',
                ];
            }
        }

        foreach ($thirdParties as $thirdParty) {
            ThirdParty::query()->updateOrCreate(
                ['company_id' => $company->id, 'tax_id' => (string) $thirdParty['id_number']],
                [
                    'type' => $this->inferThirdPartyType($thirdParty),
                    'name' => $thirdParty['name'] ?? $thirdParty['id_number'],
                    'verification_digit' => $thirdParty['verification_digit'] ?? null,
                    'email' => $thirdParty['email'] ?? null,
                    'phone' => $thirdParty['phone'] ?? null,
                    'city' => $thirdParty['city'] ?? null,
                    'address' => $thirdParty['address'] ?? null,
                ],
            );
        }

        return $thirdParties->count();
    }

    /**
     * @param  array<string, mixed>  $thirdParty
     */
    private function inferThirdPartyType(array $thirdParty): ThirdPartyType
    {
        $legalType = mb_strtolower((string) ($thirdParty['legal_type'] ?? ''));
        $name = mb_strtoupper((string) ($thirdParty['name'] ?? ''));

        if (str_contains($legalType, 'legal') || str_contains($name, ' S.A') || str_contains($name, 'S.A.S') || str_contains($name, ' E.S.P')) {
            return ThirdPartyType::LegalEntity;
        }

        return ThirdPartyType::NaturalPerson;
    }

    private function upsertCashAccounts(Company $company): int
    {
        $accounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->where('code', 'like', '11%')
            ->get();

        foreach ($accounts as $account) {
            $company->cashAccounts()->updateOrCreate(
                ['chart_account_id' => $account->id],
                [
                    'type' => CashAccountType::Bank,
                    'name' => $account->name,
                    'bank_name' => null,
                    'account_number' => null,
                    'is_active' => true,
                ],
            );
        }

        return $accounts->count();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertPeriods(Company $company, array $payload): int
    {
        $months = collect($payload['vouchers'] ?? [])
            ->mapWithKeys(function (array $voucher): array {
                $month = $this->parseDate((string) $voucher['date'])->startOfMonth();

                return [$month->format('Y-m') => $month];
            })
            ->values();

        foreach ($months as $month) {
            AccountingPeriod::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'starts_on' => $month->startOfDay()->toDateTimeString(),
                    'ends_on' => $month->endOfMonth()->startOfDay()->toDateTimeString(),
                ],
                ['is_closed' => false, 'closed_at' => null],
            );
        }

        return $months->count();
    }

    /**
     * @param  array<string, mixed>  $voucherPayload
     */
    private function importVoucher(Company $company, array $voucherPayload): string
    {
        if (($voucherPayload['validation']['balanced'] ?? false) !== true) {
            return 'Comprobante desbalanceado en archivo origen.';
        }

        $entries = $this->buildEntries($company, $voucherPayload);

        if (! Voucher::entriesAreBalanced($entries)) {
            return 'Comprobante desbalanceado después de normalizar líneas.';
        }

        $number = $this->buildVoucherNumber($voucherPayload);

        if (Voucher::query()->where('number', $number)->exists()) {
            return 'skipped';
        }

        $thirdParty = $this->findThirdParty($company, (string) ($voucherPayload['third_party_id_number'] ?? ''));
        $date = $this->parseDate((string) $voucherPayload['date']);

        $voucher = Voucher::query()->create([
            'company_id' => $company->id,
            'third_party_id' => $thirdParty?->id,
            'adjusts_voucher_id' => null,
            'type' => VoucherType::Expense,
            'status' => VoucherStatus::Approved,
            'number' => $number,
            'date' => $date->toDateString(),
            'description' => mb_substr((string) ($voucherPayload['concept'] ?? $number), 0, 255),
            'approved_at' => $date->endOfDay(),
        ]);

        foreach ($entries as $entry) {
            $voucher->entries()->create($entry);
        }

        $this->createExpenseRecord($voucher, $voucherPayload, $entries, $date);

        return 'imported';
    }

    /**
     * @param  array<string, mixed>  $voucherPayload
     */
    private function buildVoucherNumber(array $voucherPayload): string
    {
        return 'AM-'.$voucherPayload['source_document']['archive_master_id'].'-'.$this->sanitizeNumber((string) $voucherPayload['number']);
    }

    private function sanitizeNumber(string $number): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9-]+/', '-', trim($number)) ?: 'SIN-NUMERO';

        return trim($sanitized, '-');
    }

    /**
     * @param  array<string, mixed>  $voucherPayload
     * @return array<int, array{chart_account_id: int, third_party_id: int|null, description: string, debit: float, credit: float}>
     */
    private function buildEntries(Company $company, array $voucherPayload): array
    {
        $thirdParty = $this->findThirdParty($company, (string) ($voucherPayload['third_party_id_number'] ?? ''));

        return collect($voucherPayload['lines'] ?? [])
            ->map(function (array $line) use ($company, $thirdParty): array {
                $account = ChartAccount::query()
                    ->where('company_id', $company->id)
                    ->where('code', (string) $line['account_code'])
                    ->firstOrFail();

                return [
                    'chart_account_id' => $account->id,
                    'third_party_id' => $thirdParty?->id,
                    'description' => mb_substr((string) ($line['description'] ?? $account->name), 0, 255),
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                ];
            })
            ->all();
    }

    private function findThirdParty(Company $company, string $taxId): ?ThirdParty
    {
        if ($taxId === '') {
            return null;
        }

        return ThirdParty::query()
            ->where('company_id', $company->id)
            ->where('tax_id', $taxId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $voucherPayload
     * @param  array<int, array{chart_account_id: int, third_party_id: int|null, description: string, debit: float, credit: float}>  $entries
     */
    private function createExpenseRecord(Voucher $voucher, array $voucherPayload, array $entries, CarbonImmutable $date): void
    {
        $expenseEntry = collect($entries)->first(fn (array $entry): bool => $entry['debit'] > 0);
        $payableEntry = collect($entries)->first(fn (array $entry): bool => $entry['credit'] > 0);

        if (! $expenseEntry || ! $payableEntry) {
            return;
        }

        ExpenseRecord::query()->create([
            'voucher_id' => $voucher->id,
            'expense_account_id' => $expenseEntry['chart_account_id'],
            'payable_account_id' => $payableEntry['chart_account_id'],
            'support_type' => 'archive_master_preview',
            'support_number' => (string) Arr::get($voucherPayload, 'source_document.archive_master_number', $voucher->number),
            'accrual_date' => $date->toDateString(),
            'amount' => (float) ($voucherPayload['total'] ?? collect($entries)->sum('debit')),
            'withholding_amount' => $this->sumWithholdings($entries),
            'has_valid_support' => false,
            'is_deductible' => false,
        ]);
    }

    /**
     * @param  array<int, array{chart_account_id: int, third_party_id: int|null, description: string, debit: float, credit: float}>  $entries
     */
    private function sumWithholdings(array $entries): float
    {
        $accounts = ChartAccount::query()
            ->whereIn('id', collect($entries)->pluck('chart_account_id'))
            ->pluck('code', 'id');

        return collect($entries)
            ->filter(fn (array $entry): bool => str_starts_with((string) $accounts[$entry['chart_account_id']], '24') && $entry['credit'] > 0)
            ->sum('credit');
    }

    private function parseDate(string $date): CarbonImmutable
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            if (! CarbonImmutable::hasFormat($date, $format)) {
                continue;
            }

            $parsed = CarbonImmutable::createFromFormat($format, $date);

            if ($parsed !== false) {
                return $parsed->startOfDay();
            }
        }

        throw ValidationException::withMessages([
            'date' => "No se pudo interpretar la fecha [{$date}].",
        ]);
    }
}

<?php

namespace App\Services\Accounting;

use App\Enums\BankProfile;
use App\Enums\BankStatementLineStatus;
use App\Models\BankStatementImport;
use App\Models\CashAccount;
use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use League\Csv\CharsetConverter;
use League\Csv\Info;
use League\Csv\Reader;

class ImportBankStatement
{
    /**
     * Orden obligatorio: UTF-8 primero (Pitfall 2 de 03-RESEARCH.md) — casi cualquier
     * texto ASCII puro es "válido" como Windows-1252/ISO-8859-1 también, así que probar
     * un charset de 1 byte primero corrompería texto UTF-8 real con tildes.
     *
     * @var array<int, string>
     */
    private const ENCODING_CANDIDATES = ['UTF-8', 'Windows-1252', 'ISO-8859-1'];

    /**
     * @var array<int, string>
     */
    private const DELIMITER_CANDIDATES = [',', ';'];

    public function handle(Company $company, CashAccount $cashAccount, BankProfile $bank, string $absolutePath): BankStatementImport
    {
        $csv = $this->openCsv($absolutePath);

        $columns = $this->resolveColumns($csv, $bank);

        /** @var array<int, array<string, string>> $records */
        $records = iterator_to_array($csv->getRecords());

        [$startsOn, $endsOn] = $this->detectPeriod($records, $columns, $bank);

        $this->guardAgainstOverlap($cashAccount, $startsOn, $endsOn);

        return DB::transaction(function () use ($company, $cashAccount, $bank, $absolutePath, $records, $columns, $startsOn, $endsOn) {
            $import = BankStatementImport::query()->create([
                'company_id' => $company->id,
                'cash_account_id' => $cashAccount->id,
                'bank' => $bank,
                'file_name' => basename($absolutePath),
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'imported_by' => auth()->id(),
            ]);

            $this->createLines($import, $records, $columns, $bank);

            return $import->load('lines');
        });
    }

    private function openCsv(string $absolutePath): Reader
    {
        $resource = fopen($absolutePath, 'r');

        $sample = fread($resource, 8192);
        rewind($resource);

        $inputEncoding = null;

        foreach (self::ENCODING_CANDIDATES as $candidate) {
            if (mb_check_encoding($sample, $candidate)) {
                $inputEncoding = $candidate;

                break;
            }
        }

        if ($inputEncoding !== null && $inputEncoding !== 'UTF-8') {
            CharsetConverter::register();
            stream_filter_append($resource, CharsetConverter::getFiltername($inputEncoding, 'UTF-8'), STREAM_FILTER_READ);
        }

        $csv = Reader::from($resource);
        $csv->setHeaderOffset(null);

        $stats = Info::getDelimiterStats($csv, self::DELIMITER_CANDIDATES, 10);
        $delimiter = array_search(max($stats), $stats, true);

        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(0);

        return $csv;
    }

    /**
     * @return array{date: string, description: string, amount: string, reference: ?string}
     */
    private function resolveColumns(Reader $csv, BankProfile $bank): array
    {
        $header = $csv->getHeader();
        $aliases = $bank->columnAliases();

        $columns = [];

        foreach ($bank->requiredFields() as $field) {
            $match = $this->matchAlias($header, $aliases[$field] ?? []);

            if ($match === null) {
                throw ValidationException::withMessages([
                    'file' => 'El archivo no tiene encabezados de columna reconocibles. Verifica que sea un CSV exportado del banco seleccionado y vuelve a intentarlo.',
                ]);
            }

            $columns[$field] = $match;
        }

        $columns['reference'] = $this->matchAlias($header, $aliases['reference'] ?? []);

        return $columns;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string>  $aliases
     */
    private function matchAlias(array $header, array $aliases): ?string
    {
        foreach ($header as $column) {
            foreach ($aliases as $alias) {
                if (mb_strtolower(trim($column)) === mb_strtolower(trim($alias))) {
                    return $column;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, string>>  $records
     * @param  array{date: string, description: string, amount: string, reference: ?string}  $columns
     * @return array{0: string, 1: string}
     */
    private function detectPeriod(array $records, array $columns, BankProfile $bank): array
    {
        $dates = [];

        foreach ($records as $record) {
            $date = $this->parseDate((string) ($record[$columns['date']] ?? ''), $bank->dateFormats());

            if ($date !== null) {
                $dates[] = $date;
            }
        }

        if ($dates === []) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo interpretar ninguna fila del archivo. Verifica el formato y el banco seleccionado.',
            ]);
        }

        usort($dates, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        return [$dates[0]->toDateString(), $dates[array_key_last($dates)]->toDateString()];
    }

    private function guardAgainstOverlap(CashAccount $cashAccount, string $startsOn, string $endsOn): void
    {
        $overlaps = BankStatementImport::query()
            ->where('cash_account_id', $cashAccount->id)
            ->where(fn ($query) => $query
                ->whereBetween('starts_on', [$startsOn, $endsOn])
                ->orWhereBetween('ends_on', [$startsOn, $endsOn])
                ->orWhere(fn ($query) => $query->where('starts_on', '<=', $startsOn)->where('ends_on', '>=', $endsOn)))
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'file' => "Este archivo se solapa con un extracto ya importado para esta cuenta (rango {$startsOn}–{$endsOn}). Recorta el archivo para cubrir solo fechas nuevas y vuelve a intentarlo.",
            ]);
        }
    }

    /**
     * @param  array<int, string>  $formats
     */
    private function parseDate(string $raw, array $formats): ?CarbonImmutable
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        foreach ($formats as $format) {
            if (! CarbonImmutable::hasFormat($trimmed, $format)) {
                continue;
            }

            $parsed = CarbonImmutable::createFromFormat($format, $trimmed);

            if ($parsed !== false) {
                return $parsed->startOfDay();
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, string>>  $records
     * @param  array{date: string, description: string, amount: string, reference: ?string}  $columns
     */
    private function createLines(BankStatementImport $import, array $records, array $columns, BankProfile $bank): void
    {
        foreach ($records as $record) {
            $dateRaw = (string) ($record[$columns['date']] ?? '');
            $date = $this->parseDate($dateRaw, $bank->dateFormats());
            $description = $record[$columns['description']] ?? null;
            $reference = $columns['reference'] !== null ? ($record[$columns['reference']] ?? null) ?: null : null;

            if ($date === null) {
                $import->lines()->create([
                    'line_date' => null,
                    'description' => $description,
                    'amount' => null,
                    'reference' => $reference,
                    'raw_row' => $record,
                    'status' => BankStatementLineStatus::Rejected,
                    'reject_reason' => "No se pudo interpretar la fecha [{$dateRaw}].",
                ]);

                continue;
            }

            $amountRaw = (string) ($record[$columns['amount']] ?? '');
            $amount = $this->normalizeAmount($amountRaw);

            if ($amount === null) {
                $import->lines()->create([
                    'line_date' => $date->toDateString(),
                    'description' => $description,
                    'amount' => null,
                    'reference' => $reference,
                    'raw_row' => $record,
                    'status' => BankStatementLineStatus::Rejected,
                    'reject_reason' => "El valor de la columna monto no es numérico: [{$amountRaw}].",
                ]);

                continue;
            }

            $import->lines()->create([
                'line_date' => $date->toDateString(),
                'description' => $description,
                'amount' => $amount,
                'reference' => $reference,
                'raw_row' => $record,
                'status' => BankStatementLineStatus::Pending,
            ]);
        }
    }

    private function normalizeAmount(string $raw): ?float
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        $clean = str_replace(['$', ' ', '+'], '', $trimmed);

        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }
}

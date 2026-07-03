<?php

namespace App\Services\Accounting;

class ValidateColombianTaxId
{
    /**
     * @var array<int, int>
     */
    private const WEIGHTS = [71, 67, 59, 53, 47, 43, 41, 37, 29, 23, 19, 17, 13, 7, 3];

    public function verificationDigit(string $taxId): int
    {
        $digits = preg_replace('/\D+/', '', $taxId) ?? '';
        $weights = array_slice(self::WEIGHTS, -strlen($digits));
        $sum = 0;

        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * $weights[$index];
        }

        $remainder = $sum % 11;

        return $remainder > 1 ? 11 - $remainder : $remainder;
    }

    public function passes(string $taxId, int|string|null $verificationDigit): bool
    {
        if ($verificationDigit === null || $verificationDigit === '') {
            return true;
        }

        return $this->verificationDigit($taxId) === (int) $verificationDigit;
    }
}

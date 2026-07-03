<?php

use App\Services\Accounting\ValidateColombianTaxId;

it('calculates Colombian DIAN verification digits', function (string $taxId, int $digit) {
    $validator = new ValidateColombianTaxId;

    expect($validator->verificationDigit($taxId))->toBe($digit)
        ->and($validator->passes($taxId, $digit))->toBeTrue()
        ->and($validator->passes($taxId, ($digit + 1) % 10))->toBeFalse();
})->with([
    ['900373913', 4],
    ['800197268', 4],
    ['901362343', 2],
]);

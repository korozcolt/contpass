<?php

use App\Enums\AccountNature;
use App\Enums\CashAccountType;
use App\Enums\PaymentMethod;
use App\Enums\ThirdPartyType;
use App\Enums\UserRole;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

it('exposes Spanish labels, colors and icons for Filament badges', function (object $case): void {
    expect($case)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class)
        ->and($case->getLabel())->toBeString()->not->toBe($case->value)
        ->and($case->getColor())->toBeString()->not->toBeEmpty()
        ->and($case->getIcon())->toBeInstanceOf(Heroicon::class);
})->with([
    ...AccountNature::cases(),
    ...CashAccountType::cases(),
    ...PaymentMethod::cases(),
    ...ThirdPartyType::cases(),
    ...UserRole::cases(),
    ...VoucherStatus::cases(),
    ...VoucherType::cases(),
]);

it('keeps the legacy label method aligned with Filament labels', function (object $case): void {
    expect($case->label())->toBe($case->getLabel());
})->with([
    ...AccountNature::cases(),
    ...CashAccountType::cases(),
    ...PaymentMethod::cases(),
    ...ThirdPartyType::cases(),
    ...UserRole::cases(),
    ...VoucherStatus::cases(),
    ...VoucherType::cases(),
]);

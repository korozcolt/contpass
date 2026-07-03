<?php

namespace App\Filament\Support;

use App\Models\ChartAccount;
use App\Models\ThirdParty;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class AccountingFormFields
{
    public static function companyId(): Hidden
    {
        return Hidden::make('company_id')
            ->default(fn (): int => app(CurrentCompany::class)->get()->id)
            ->required();
    }

    public static function money(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->step('0.01')
            ->minValue(0)
            ->prefix('COP $')
            ->prefixIcon(Heroicon::CurrencyDollar)
            ->inputMode('decimal')
            ->required();
    }

    public static function percent(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->step('0.0001')
            ->minValue(0)
            ->maxValue(100)
            ->suffix('%')
            ->required();
    }

    public static function date(string $name, string $label): DatePicker
    {
        return DatePicker::make($name)
            ->label($label)
            ->native(false)
            ->displayFormat('Y-m-d')
            ->default(now())
            ->required();
    }

    public static function thirdParty(string $name = 'third_party_id'): Select
    {
        return Select::make($name)
            ->label('Tercero')
            ->options(fn (): array => ThirdParty::query()
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (ThirdParty $thirdParty) => [
                    $thirdParty->id => "{$thirdParty->tax_id}-{$thirdParty->verification_digit} · {$thirdParty->name}",
                ])
                ->all())
            ->searchable()
            ->preload()
            ->required();
    }

    public static function chartAccount(string $name, string $label, ?string $classPrefix = null): Select
    {
        return self::chartAccountPrefixes($name, $label, $classPrefix === null ? [] : [$classPrefix]);
    }

    /**
     * @param  array<int, string>  $classPrefixes
     */
    public static function chartAccountPrefixes(string $name, string $label, array $classPrefixes): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (): array => ChartAccount::query()
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->when($classPrefixes !== [], fn ($query) => $query->where(function ($query) use ($classPrefixes): void {
                    foreach ($classPrefixes as $prefix) {
                        $query->orWhere('code', 'like', "{$prefix}%");
                    }
                }))
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn (ChartAccount $account) => [$account->id => "{$account->code} · {$account->name}"])
                ->all())
            ->searchable()
            ->preload()
            ->required()
            ->rule(function () use ($classPrefixes): ?Exists {
                if ($classPrefixes === []) {
                    return null;
                }

                return Rule::exists('chart_accounts', 'id')
                    ->where('company_id', app(CurrentCompany::class)->get()->id)
                    ->where(function ($query) use ($classPrefixes): void {
                        foreach ($classPrefixes as $prefix) {
                            $query->orWhere('code', 'like', "{$prefix}%");
                        }
                    });
            });
    }

    public static function voucher(string $name = 'source_voucher_id'): Select
    {
        return Select::make($name)
            ->label('Comprobante origen')
            ->options(fn (): array => Voucher::query()
                ->with('thirdParty')
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->latest()
                ->limit(100)
                ->get()
                ->mapWithKeys(fn (Voucher $voucher) => [
                    $voucher->id => "{$voucher->number} · {$voucher->thirdParty?->name} · {$voucher->description}",
                ])
                ->all())
            ->searchable()
            ->preload();
    }
}

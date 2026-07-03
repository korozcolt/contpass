<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('third_party_id')
                    ->relationship('thirdParty', 'name'),
                Select::make('adjusts_voucher_id')
                    ->relationship('adjustsVoucher', 'id'),
                Select::make('type')
                    ->options(VoucherType::class)
                    ->required(),
                Select::make('status')
                    ->options(VoucherStatus::class)
                    ->required(),
                TextInput::make('number')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                DateTimePicker::make('approved_at'),
            ]);
    }
}

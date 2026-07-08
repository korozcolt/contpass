<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Filament\Support\AccountingFormFields;
use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
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
            ->columns(2)
            ->schema([
                AccountingFormFields::companyId(),

                TextInput::make('number')
                    ->label('Número de comprobante')
                    ->placeholder('Ej: COMP-001')
                    ->required()
                    ->maxLength(50),

                Select::make('type')
                    ->label('Tipo de comprobante')
                    ->options(VoucherType::class)
                    ->required()
                    ->native(false),

                Select::make('status')
                    ->label('Estado')
                    ->options(VoucherStatus::class)
                    ->required()
                    ->default(VoucherStatus::Draft)
                    ->native(false),

                AccountingFormFields::thirdParty('third_party_id')
                    ->label('Tercero')
                    ->required(false),

                Select::make('adjusts_voucher_id')
                    ->label('Comprobante a ajustar')
                    ->helperText('Opcional. Selecciona si este comprobante ajusta o reversa a otro existente.')
                    ->options(fn (): array => Voucher::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderByDesc('id')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(fn (Voucher $v) => [$v->id => "{$v->number} · {$v->description}"])
                        ->all())
                    ->searchable()
                    ->preload(),

                DatePicker::make('date')
                    ->label('Fecha de registro')
                    ->required()
                    ->native(false)
                    ->default(today()),

                DateTimePicker::make('approved_at')
                    ->label('Fecha de aprobación')
                    ->native(false)
                    ->disabled()
                    ->placeholder('Se asigna al aprobar'),

                TextInput::make('description')
                    ->label('Concepto / Descripción')
                    ->placeholder('Escribe una breve descripción del comprobante')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}

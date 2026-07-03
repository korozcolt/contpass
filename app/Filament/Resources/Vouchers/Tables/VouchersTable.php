<?php

namespace App\Filament\Resources\Vouchers\Tables;

use App\Filament\Support\AccountingFormFields;
use App\Models\Voucher;
use App\Services\Accounting\CreateAdjustmentVoucher;
use App\Services\Accounting\CurrentCompany;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->searchable()->sortable(),
                TextColumn::make('date')->label('Fecha')->date()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('thirdParty.name')->label('Tercero')->searchable()->placeholder('-'),
                TextColumn::make('description')->label('Descripción')->searchable()->limit(60),
                TextColumn::make('approved_at')->label('Aprobado')->dateTime()->toggleable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('adjust')
                    ->label('Nota de ajuste')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->modalHeading(fn (Voucher $record): string => "Ajustar {$record->number}")
                    ->form([
                        AccountingFormFields::date('date', 'Fecha del ajuste'),
                        TextInput::make('description')->label('Descripción')->required()->maxLength(255),
                        Repeater::make('entries')
                            ->label('Líneas contables')
                            ->schema([
                                AccountingFormFields::chartAccount('chart_account_id', 'Cuenta PUC'),
                                TextInput::make('description')->label('Detalle')->required(),
                                AccountingFormFields::money('debit', 'Débito')->required(false)->default(0),
                                AccountingFormFields::money('credit', 'Crédito')->required(false)->default(0),
                            ])
                            ->columns(4)
                            ->minItems(2)
                            ->required(),
                    ])
                    ->action(function (Voucher $record, array $data): void {
                        app(CreateAdjustmentVoucher::class)->handle(
                            app(CurrentCompany::class)->get(),
                            $record,
                            $data['date'],
                            $data['description'],
                            $data['entries'],
                        );

                        Notification::make()->success()->title('Nota de ajuste creada')->send();
                    }),
            ]);
    }
}

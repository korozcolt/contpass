<?php

namespace App\Filament\Resources\IncomeRecords\Tables;

use App\Models\ExternalInvoiceReference;
use App\Models\IncomeRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncomeRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('accrual_date', 'desc')
            ->columns([
                TextColumn::make('accrual_date')->label('Fecha')->date()->sortable(),
                TextColumn::make('voucher.number')->label('Comprobante')->searchable(),
                TextColumn::make('voucher.thirdParty.name')->label('Tercero')->searchable(),
                TextColumn::make('support_number')->label('Soporte')->searchable(),
                TextColumn::make('revenueAccount.full_name')->label('Ingreso')->toggleable(),
                TextColumn::make('amount')->label('Valor')->money('COP')->sortable(),
            ])
            ->recordActions([
                Action::make('external_invoice')
                    ->label(fn (IncomeRecord $record): string => $record->externalInvoiceReference
                        ? 'Ver/editar factura externa'
                        : 'Registrar factura externa')
                    ->icon(Heroicon::DocumentText)
                    ->color('gray')
                    ->fillForm(fn (IncomeRecord $record): array => $record->externalInvoiceReference?->toArray() ?? [])
                    ->form([
                        TextInput::make('invoice_number')
                            ->label('Número de factura')
                            ->required()
                            ->unique(
                                table: 'external_invoice_references',
                                column: 'invoice_number',
                                ignorable: fn (IncomeRecord $record): ?ExternalInvoiceReference => $record->externalInvoiceReference,
                                ignoreRecord: true,
                            )
                            ->maxLength(255),
                        TextInput::make('cufe')->label('CUFE')->maxLength(255),
                        TextInput::make('provider')->label('Proveedor')->maxLength(255),
                        TextInput::make('document_url')->label('URL del documento')->url()->maxLength(255),
                    ])
                    ->action(function (IncomeRecord $record, array $data): void {
                        $record->externalInvoiceReference()->updateOrCreate([], $data);

                        Notification::make()->success()->title('Factura externa registrada')->send();
                    }),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Services\Accounting\ConvertQuotationToIncome;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->searchable()->sortable(),
                TextColumn::make('thirdParty.name')->label('Tercero')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->state(fn (Quotation $record) => $record->effectiveStatus()),
                TextColumn::make('total')->label('Total')->money('COP')
                    ->state(fn (Quotation $record) => $record->total),
                TextColumn::make('expires_on')->label('Vigencia hasta')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(QuotationStatus::class),
            ])
            ->emptyStateHeading('No hay cotizaciones')
            ->emptyStateDescription("Crea una cotización para un tercero desde el botón 'Nueva cotización'.")
            ->recordActions([
                EditAction::make(),
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon(Heroicon::DocumentArrowDown)
                    ->url(fn (Quotation $record): string => route('quotations.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('send')
                    ->label('Enviar')
                    ->icon(Heroicon::PaperAirplane)
                    ->color('primary')
                    ->visible(fn (Quotation $record): bool => $record->status === QuotationStatus::Draft)
                    ->action(function (Quotation $record): void {
                        $record->forceFill(['status' => QuotationStatus::Sent])->save();

                        Notification::make()->success()->title('Cotización enviada')->send();
                    }),
                Action::make('accept')
                    ->label('Aceptar')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (Quotation $record): bool => $record->effectiveStatus() === QuotationStatus::Sent)
                    ->action(function (Quotation $record): void {
                        $record->forceFill(['status' => QuotationStatus::Accepted])->save();

                        Notification::make()->success()->title('Cotización aceptada')->send();
                    }),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (Quotation $record): bool => $record->effectiveStatus() === QuotationStatus::Sent)
                    ->modalHeading(fn (Quotation $record): string => "Rechazar {$record->number}")
                    ->modalDescription('Vas a marcar esta cotización como Rechazada. Indica el motivo:')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Motivo de rechazo')
                            ->required()
                            ->validationMessages(['required' => 'Debes indicar un motivo de rechazo.'])
                            ->maxLength(255),
                    ])
                    ->action(function (Quotation $record, array $data): void {
                        $record->forceFill([
                            'status' => QuotationStatus::Rejected,
                            'rejection_reason' => $data['rejection_reason'],
                        ])->save();

                        Notification::make()->success()->title('Cotización rechazada')->send();
                    }),
                Action::make('convert')
                    ->label('Convertir a ingreso')
                    ->icon(Heroicon::Banknotes)
                    ->color('success')
                    ->visible(fn (Quotation $record): bool => $record->status === QuotationStatus::Accepted && $record->voucher_id === null)
                    ->requiresConfirmation()
                    ->modalHeading('Convertir a ingreso')
                    ->modalDescription('Esta acción creará un comprobante de ingreso contable y no se puede deshacer. ¿Continuar?')
                    ->action(function (Quotation $record): void {
                        app(ConvertQuotationToIncome::class)->handle($record);

                        Notification::make()->success()->title('Cotización convertida a ingreso')->send();
                    }),
            ]);
    }
}

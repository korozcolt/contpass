<?php

namespace App\Filament\Resources\BudgetObligations\Tables;

use App\Enums\BudgetObligationStatus;
use App\Filament\Resources\Vouchers\VoucherResource;
use App\Models\BudgetObligation;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\ApproveBudgetObligation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetObligationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->searchable()->sortable(),
                TextColumn::make('budgetRegistration.number')->label('RP')->searchable()->placeholder('-'),
                TextColumn::make('amount')->label('Monto')->money('COP')->sortable(),
                TextColumn::make('support_number')->label('Soporte')->searchable()->placeholder('-'),
                TextColumn::make('support_type')->label('Tipo soporte')->placeholder('-'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('approved_at')->label('Aprobado')->dateTime()->toggleable()->placeholder('-'),
                TextColumn::make('accrual_date')->label('Fecha causación')->date()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Aprobar y Causar')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->visible(fn (BudgetObligation $record): bool => $record->status === BudgetObligationStatus::Draft)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Aprobación')
                    ->modalDescription(fn (BudgetObligation $record): string => "¿Está seguro de aprobar la obligación {$record->number} por \$".number_format((float) $record->amount, 2).'? Esta acción causará el egreso en el PUC automáticamente y generará el comprobante contable.')
                    ->modalSubmitActionLabel('Aprobar y Causar')
                    ->action(function (BudgetObligation $record): void {
                        $approved = app(ApproveBudgetObligation::class)->handle(
                            app(CurrentCompany::class)->get(),
                            $record,
                        );

                        Notification::make()
                            ->success()
                            ->title('Obligación aprobada y causada')
                            ->body("Comprobante {$approved->voucher->number} generado automáticamente.")
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view_voucher')
                                    ->label('Ver comprobante')
                                    ->url(fn (): string => VoucherResource::getUrl('view', ['record' => $approved->voucher])),
                            ])
                            ->send();
                    }),
                Action::make('view_voucher')
                    ->label('Ver comprobante')
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->color('gray')
                    ->visible(fn (BudgetObligation $record): bool => $record->voucher_id !== null)
                    ->url(fn (BudgetObligation $record): string => VoucherResource::getUrl('view', ['record' => $record->voucher_id]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

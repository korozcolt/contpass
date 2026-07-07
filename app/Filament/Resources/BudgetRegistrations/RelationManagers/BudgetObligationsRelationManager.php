<?php

namespace App\Filament\Resources\BudgetRegistrations\RelationManagers;

use App\Enums\BudgetObligationStatus;
use App\Filament\Resources\Vouchers\VoucherResource;
use App\Models\BudgetObligation;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\ApproveBudgetObligation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Actions\Action as NotifAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetObligationsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetObligations';

    protected static ?string $title = 'Obligaciones Presupuestales';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('support_type')
                    ->label('Tipo soporte')
                    ->placeholder('—'),
                TextColumn::make('support_number')
                    ->label('Soporte')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('accrual_date')
                    ->label('Causación')
                    ->date()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Aprobado')
                    ->dateTime()
                    ->toggleable()
                    ->placeholder('—'),
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
                    ->modalDescription(fn (BudgetObligation $record): string => "¿Confirmar aprobación de {$record->number} por $".number_format((float) $record->amount, 2).'? Se generará el comprobante contable automáticamente.')
                    ->modalSubmitActionLabel('Aprobar y Causar')
                    ->action(function (BudgetObligation $record): void {
                        $approved = app(ApproveBudgetObligation::class)->handle(
                            app(CurrentCompany::class)->get(),
                            $record,
                        );

                        Notification::make()
                            ->success()
                            ->title('Obligación aprobada y causada')
                            ->body("Comprobante {$approved->voucher->number} generado.")
                            ->actions([
                                NotifAction::make('view_voucher')
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
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

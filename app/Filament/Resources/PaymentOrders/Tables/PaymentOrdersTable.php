<?php

namespace App\Filament\Resources\PaymentOrders\Tables;

use App\Enums\PaymentOrderStatus;
use App\Filament\Support\AccountingFormFields;
use App\Models\PaymentOrder;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\ExecutePaymentOrder;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PaymentOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('issued_on', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->searchable()->sortable(),
                TextColumn::make('budgetObligation.number')->label('Obligación')->searchable()->placeholder('-'),
                TextColumn::make('cashAccount.name')->label('Cuenta bancaria')->searchable()->placeholder('-'),
                TextColumn::make('amount')->label('Monto')->money('COP')->sortable(),
                TextColumn::make('method')->label('Medio de pago')->badge(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('issued_on')->label('Emitido')->date()->sortable(),
                TextColumn::make('paid_on')->label('Pagado')->date()->sortable()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(PaymentOrderStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon(Heroicon::CheckCircle)
                    ->color('primary')
                    ->visible(fn (PaymentOrder $record): bool => $record->status === PaymentOrderStatus::Pending)
                    ->action(function (PaymentOrder $record): void {
                        $record->forceFill(['status' => PaymentOrderStatus::Approved])->save();

                        Notification::make()->success()->title('Orden de pago aprobada')->send();
                    }),
                Action::make('execute')
                    ->label('Ejecutar pago')
                    ->icon(Heroicon::Banknotes)
                    ->color('success')
                    ->visible(fn (PaymentOrder $record): bool => $record->status === PaymentOrderStatus::Approved)
                    ->modalHeading(fn (PaymentOrder $record): string => "Ejecutar pago {$record->number}")
                    ->modalDescription(fn (PaymentOrder $record): string => 'Monto: $'.number_format((float) $record->amount, 2))
                    ->form([
                        AccountingFormFields::date('paid_on', 'Fecha de pago'),
                        TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(100),
                    ])
                    ->action(function (PaymentOrder $record, array $data): void {
                        app(ExecutePaymentOrder::class)->handle(
                            app(CurrentCompany::class)->get(),
                            $record,
                            $data['paid_on'],
                            $data['reference'] ?? null,
                        );

                        Notification::make()->success()->title('Pago ejecutado exitosamente')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                BulkAction::make('bulk_approve')
                    ->label('Aprobar seleccionadas')
                    ->icon(Heroicon::CheckCircle)
                    ->color('primary')
                    ->action(function (Collection $records): void {
                        $records->each(function (PaymentOrder $order): void {
                            if ($order->status === PaymentOrderStatus::Pending) {
                                $order->forceFill(['status' => PaymentOrderStatus::Approved])->save();
                            }
                        });

                        Notification::make()->success()->title('Órdenes aprobadas')->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('bulk_execute')
                    ->label('Pagar seleccionadas')
                    ->icon(Heroicon::Banknotes)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Ejecutar pagos seleccionados')
                    ->modalDescription('Se ejecutarán todas las órdenes en estado Aprobada con fecha de hoy.')
                    ->action(function (Collection $records): void {
                        $executor = app(ExecutePaymentOrder::class);
                        $company = app(CurrentCompany::class)->get();
                        $today = now()->toDateString();

                        $records->each(function (PaymentOrder $order) use ($executor, $company, $today): void {
                            if ($order->status === PaymentOrderStatus::Approved) {
                                $executor->handle($company, $order, $today);
                            }
                        });

                        Notification::make()->success()->title('Pagos ejecutados')->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}

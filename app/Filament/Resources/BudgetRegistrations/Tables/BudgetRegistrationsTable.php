<?php

namespace App\Filament\Resources\BudgetRegistrations\Tables;

use App\Enums\BudgetRegistrationStatus;
use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetRegistration;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\CreateBudgetObligation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetRegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('issued_on', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->searchable()->sortable(),
                TextColumn::make('budgetAvailabilityCertificate.number')->label('CDP')->searchable()->placeholder('-'),
                TextColumn::make('thirdParty.name')->label('Tercero')->searchable()->placeholder('-'),
                TextColumn::make('amount')->label('Monto')->money('COP')->sortable(),
                TextColumn::make('available_for_obligation')->label('Saldo disponible')->money('COP')
                    ->sortable()
                    ->color(fn (BudgetRegistration $record): string => $record->available_for_obligation <= 0 ? 'danger' : 'success'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('issued_on')->label('Emitido')->date()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('create_obligation')
                    ->label('Registrar Obligación')
                    ->icon(Heroicon::DocumentPlus)
                    ->color('gray')
                    ->visible(fn (BudgetRegistration $record): bool => $record->status === BudgetRegistrationStatus::Active && $record->available_for_obligation > 0)
                    ->modalHeading(fn (BudgetRegistration $record): string => "Registrar obligación sobre {$record->number}")
                    ->modalDescription(fn (BudgetRegistration $record): string => 'Saldo disponible del RP: $'.number_format($record->available_for_obligation, 2))
                    ->form(fn (BudgetRegistration $record): array => [
                        AccountingFormFields::money('amount', 'Monto de la obligación')
                            ->hint('Saldo disponible del RP: $'.number_format($record->available_for_obligation, 2))
                            ->maxValue($record->available_for_obligation),
                        TextInput::make('support_type')
                            ->label('Tipo de soporte')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Factura, Cuenta de cobro, Contrato...'),
                        TextInput::make('support_number')
                            ->label('Número de soporte')
                            ->required()
                            ->maxLength(100),
                        AccountingFormFields::date('accrual_date', 'Fecha de causación'),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->maxLength(500),
                    ])
                    ->action(function (BudgetRegistration $record, array $data): void {
                        app(CreateBudgetObligation::class)->handle(
                            app(CurrentCompany::class)->get(),
                            $record,
                            (float) $data['amount'],
                            $data['support_type'],
                            $data['support_number'],
                            $data['accrual_date'],
                            $data['description'] ?? null,
                        );

                        Notification::make()->success()->title('Obligación registrada en borrador')->send();
                    }),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

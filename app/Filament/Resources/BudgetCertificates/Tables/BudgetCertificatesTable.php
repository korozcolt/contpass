<?php

namespace App\Filament\Resources\BudgetCertificates\Tables;

use App\Enums\BudgetCertificateStatus;
use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetAvailabilityCertificate;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\ApplyBudgetRegistration;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetCertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('issued_on', 'desc')
            ->columns([
                TextColumn::make('number')->label('Número')->searchable()->sortable(),
                TextColumn::make('budgetAppropriation.name')->label('Rubro')->searchable()->placeholder('-'),
                TextColumn::make('amount')->label('Monto')->money('COP')->sortable(),
                TextColumn::make('available_for_registration')->label('Saldo disponible')->money('COP')
                    ->sortable()
                    ->color(fn (BudgetAvailabilityCertificate $record): string => $record->available_for_registration <= 0 ? 'danger' : 'success'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('issued_on')->label('Emitido')->date()->sortable(),
                TextColumn::make('expires_on')->label('Vence')->date()->placeholder('-'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('apply_rp')
                    ->label('Emitir RP')
                    ->icon(Heroicon::DocumentArrowUp)
                    ->color('success')
                    ->visible(fn (BudgetAvailabilityCertificate $record): bool => $record->status === BudgetCertificateStatus::Active && $record->available_for_registration > 0)
                    ->modalHeading(fn (BudgetAvailabilityCertificate $record): string => "Emitir RP sobre {$record->number}")
                    ->modalDescription(fn (BudgetAvailabilityCertificate $record): string => 'Saldo disponible del CDP: $'.number_format($record->available_for_registration, 2))
                    ->form(fn (BudgetAvailabilityCertificate $record): array => [
                        AccountingFormFields::thirdParty()->required(),
                        AccountingFormFields::date('issued_on', 'Fecha de emisión'),
                        AccountingFormFields::money('amount', 'Monto del RP')
                            ->hint('Saldo disponible del CDP: $'.number_format($record->available_for_registration, 2))
                            ->maxValue($record->available_for_registration),
                        TextInput::make('justification')
                            ->label('Objeto del contrato')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (BudgetAvailabilityCertificate $record, array $data): void {
                        app(ApplyBudgetRegistration::class)->handle(
                            app(CurrentCompany::class)->get(),
                            $record,
                            ThirdParty::findOrFail($data['third_party_id']),
                            (float) $data['amount'],
                            $data['justification'],
                            $data['issued_on'],
                        );

                        Notification::make()->success()->title('Registro Presupuestal emitido')->send();
                    }),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}

<?php

namespace App\Filament\Resources\BudgetCertificates\RelationManagers;

use App\Enums\BudgetRegistrationStatus;
use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetRegistration;
use App\Services\Accounting\CurrentCompany;
use App\Services\Budget\CreateBudgetObligation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetRegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetRegistrations';

    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'Registros Presupuestales (RPs)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id),
                AccountingFormFields::thirdParty()->required(),
                AccountingFormFields::date('issued_on', 'Fecha de emisión'),
                AccountingFormFields::money('amount', 'Monto del RP'),
                TextInput::make('justification')
                    ->label('Objeto del contrato')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->defaultSort('issued_on', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('thirdParty.name')
                    ->label('Tercero')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('available_for_obligation')
                    ->label('Saldo disponible')
                    ->money('COP')
                    ->color(fn (BudgetRegistration $record): string => $record->available_for_obligation <= 0 ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('issued_on')
                    ->label('Emitido')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Emitir RP')
                    ->icon(Heroicon::DocumentPlus),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('create_obligation')
                    ->label('Registrar Obligación')
                    ->icon(Heroicon::PlusCircle)
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
                Action::make('view_obligations')
                    ->label('Ver Obligaciones')
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->color('info')
                    ->url(fn (BudgetRegistration $record): string => route(
                        'filament.admin.resources.budget-registrations.edit',
                        $record
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\BudgetAppropriations\RelationManagers;

use App\Filament\Support\AccountingFormFields;
use App\Models\BudgetAvailabilityCertificate;
use App\Services\Accounting\CurrentCompany;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetCertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetCertificates';

    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'Certificados de Disponibilidad Presupuestal (CDPs)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id),
                AccountingFormFields::date('issued_on', 'Fecha de emisión'),
                AccountingFormFields::money('amount', 'Monto del CDP'),
                AccountingFormFields::date('expires_on', 'Fecha de vencimiento')->required(false),
                Textarea::make('justification')
                    ->label('Objeto del gasto')
                    ->required()
                    ->maxLength(1000)
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
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('available_for_registration')
                    ->label('Saldo disponible')
                    ->money('COP')
                    ->color(fn (BudgetAvailabilityCertificate $record): string => $record->available_for_registration <= 0 ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('issued_on')
                    ->label('Emitido')
                    ->date()
                    ->sortable(),
                TextColumn::make('expires_on')
                    ->label('Vence')
                    ->date()
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Emitir CDP')
                    ->icon(Heroicon::DocumentPlus),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view_rps')
                    ->label('Ver RPs')
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->color('info')
                    ->url(fn (BudgetAvailabilityCertificate $record): string => route(
                        'filament.admin.resources.budget-certificates.edit',
                        $record
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

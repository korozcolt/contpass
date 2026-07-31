<?php

namespace App\Filament\Resources\PettyCashFunds\RelationManagers;

use App\Enums\PettyCashMovementType;
use App\Filament\Support\AccountingFormFields;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'Movimientos (Auxiliar)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('type')
                    ->label('Tipo de movimiento')
                    ->options(PettyCashMovementType::class)
                    ->required(),
                AccountingFormFields::date('date', 'Fecha'),
                AccountingFormFields::money('amount', 'Monto'),
                Select::make('third_party_id')
                    ->label('Beneficiario')
                    ->options(fn (): array => ThirdParty::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('support_number')
                    ->label('Soporte')
                    ->placeholder('Ej: Recibo N° 045'),
                TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('thirdParty.name')
                    ->label('Beneficiario')
                    ->placeholder('—'),
                TextColumn::make('support_number')
                    ->label('Soporte')
                    ->placeholder('—'),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(PettyCashMovementType::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar Movimiento')
                    ->icon(Heroicon::PencilSquare)
                    ->after(fn () => $this->ownerRecord->refresh()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

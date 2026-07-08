<?php

namespace App\Filament\Resources\BudgetAppropriations\RelationManagers;

use App\Enums\BudgetModificationType;
use App\Models\BudgetAppropriation;
use App\Services\Accounting\CurrentCompany;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BudgetModificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetModifications';

    protected static ?string $title = 'Historial de Modificaciones Presupuestales';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Hidden::make('company_id')
                    ->default(fn (): int => app(CurrentCompany::class)->get()->id),
                Hidden::make('user_id')
                    ->default(fn (): int => Auth::id()),
                Select::make('type')
                    ->label('Tipo de modificación')
                    ->options(BudgetModificationType::class)
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Select::make('source_appropriation_id')
                    ->label('Rubro origen (cede saldo)')
                    ->options(fn (): array => BudgetAppropriation::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->active()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn (BudgetAppropriation $r) => [
                            $r->id => "{$r->code} · {$r->name} (Saldo: $".number_format($r->available_amount, 2).')',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn ($get): bool => $get('type') === BudgetModificationType::Transfer->value)
                    ->helperText('Solo para traslados: el rubro que cede saldo.'),
                TextInput::make('document_reference')
                    ->label('Referencia del acto administrativo')
                    ->placeholder('Ej: Decreto Municipal 014 de 2026 / Acta de Junta N° 45')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Monto')
                    ->prefix('$')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                DatePicker::make('effective_date')
                    ->label('Fecha de vigencia')
                    ->required()
                    ->default(today()),
                Textarea::make('justification')
                    ->label('Justificación')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_reference')
            ->defaultSort('effective_date', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('document_reference')
                    ->label('Acto administrativo')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('sourceAppropriation.code')
                    ->label('Origen')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('effective_date')
                    ->label('Vigencia')
                    ->date()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Registró')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar Modificación')
                    ->icon(Heroicon::PencilSquare)
                    ->after(fn () => $this->ownerRecord->refresh()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

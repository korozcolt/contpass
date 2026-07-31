<?php

namespace App\Filament\Resources\BudgetModifications\Schemas;

use App\Enums\BudgetModificationType;
use App\Models\BudgetAppropriation;
use App\Services\Accounting\CurrentCompany;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BudgetModificationForm
{
    public static function configure(Schema $schema): Schema
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
                    ->visible(fn ($get): bool => $get('type') === BudgetModificationType::Transfer)
                    ->helperText('Solo para traslados: el rubro que cede saldo.'),
                Select::make('destination_appropriation_id')
                    ->label('Rubro destino (recibe recursos)')
                    ->options(fn (): array => BudgetAppropriation::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->active()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn (BudgetAppropriation $r) => [
                            $r->id => "{$r->code} · {$r->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
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
}

<?php

namespace App\Filament\Pages;

use App\Enums\BankStatementLineStatus;
use App\Enums\BankStatementMatchStatus;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankStatementMatch;
use App\Services\Accounting\ConfirmBankStatementMatch;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BankStatementReview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Tesorería';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.bank-statement-review';

    public ?int $importId = null;

    public function mount(): void
    {
        $this->importId = (int) request()->query('import');
    }

    public function import(): BankStatementImport
    {
        return BankStatementImport::query()
            ->with('cashAccount')
            ->findOrFail($this->importId);
    }

    public function getTitle(): string|Htmlable
    {
        $import = $this->import();

        return "Revisión de extracto — {$import->bank->getLabel()} · {$import->cashAccount->name}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BankStatementLine::query()
                ->where('bank_statement_import_id', $this->importId)
                ->where('status', BankStatementLineStatus::Pending)
                ->with('matches.payments')
                ->orderBy('line_date'))
            ->columns([
                TextColumn::make('line_date')
                    ->label('Fecha')
                    ->date('Y-m-d'),
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->placeholder('—'),
                TextColumn::make('candidates')
                    ->label('Candidatos')
                    ->state(fn (BankStatementLine $record): int => $record->matches->where('status', BankStatementMatchStatus::Proposed)->count())
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirmar cruce')
                    ->color('primary')
                    ->visible(fn (BankStatementLine $record): bool => $this->proposedMatches($record)->isNotEmpty())
                    ->schema(fn (BankStatementLine $record): array => [$this->matchSelect($record)])
                    ->action(function (array $data): void {
                        $match = BankStatementMatch::query()->findOrFail($data['match_id']);

                        app(ConfirmBankStatementMatch::class)->handle($match, auth()->id());
                    }),
                Action::make('discard')
                    ->label('Descartar sugerencia')
                    ->color('danger')
                    ->visible(fn (BankStatementLine $record): bool => $this->proposedMatches($record)->isNotEmpty())
                    ->schema(fn (BankStatementLine $record): array => [$this->matchSelect($record)])
                    ->action(function (array $data): void {
                        BankStatementMatch::query()
                            ->findOrFail($data['match_id'])
                            ->update(['status' => BankStatementMatchStatus::Discarded]);
                    }),
            ])
            ->emptyStateHeading('Todo conciliado')
            ->emptyStateDescription('No quedan líneas pendientes por revisar en este extracto.');
    }

    /**
     * @return Collection<int, BankStatementMatch>
     */
    private function proposedMatches(BankStatementLine $record): Collection
    {
        return $record->matches->where('status', BankStatementMatchStatus::Proposed);
    }

    private function matchSelect(BankStatementLine $record): Select
    {
        $proposed = $this->proposedMatches($record);

        return Select::make('match_id')
            ->label('Sugerencia')
            ->options($proposed->mapWithKeys(fn (BankStatementMatch $match): array => [
                $match->id => sprintf(
                    '%s — %s',
                    $match->confidence->getLabel(),
                    $match->payments->pluck('reference')->filter()->implode(', ') ?: 'sin referencia',
                ),
            ])->all())
            ->default($proposed->sortByDesc('confidence')->first()?->id)
            ->required()
            ->native(false);
    }

    public function content(Schema $schema): Schema
    {
        $rejected = $this->import()->lines()
            ->where('status', BankStatementLineStatus::Rejected)
            ->get();

        $components = [];

        if ($rejected->isNotEmpty()) {
            $components[] = Section::make('Filas rechazadas')
                ->description(sprintf(
                    '%d fila(s) no se pudieron leer y no se importaron. Revisa el motivo de cada una y corrige el archivo original antes de reintentar.',
                    $rejected->count(),
                ))
                ->schema([
                    UnorderedList::make(
                        $rejected->map(fn (BankStatementLine $line): string => trim(sprintf(
                            '%s: %s',
                            $line->description ?: 'Fila sin descripción',
                            $line->reject_reason,
                        )))->all()
                    ),
                ]);
        }

        $components[] = EmbeddedTable::make();

        return $schema->components($components);
    }
}

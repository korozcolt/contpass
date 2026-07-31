<?php

namespace App\Filament\Pages;

use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\FinancialStatement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GeneralLedgerReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static ?string $navigationLabel = 'Libro mayor';

    protected static ?string $title = 'Libro mayor';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.general-ledger-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Saldo inicial, movimiento y saldo final por cuenta')
            ->description('Cuentas del Plan Único de Cuentas con saldo arrastrado del periodo anterior y movimiento del rango seleccionado.')
            ->columns([
                TextColumn::make('code')
                    ->label('Cuenta')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('opening_balance')
                    ->label('Saldo Inicial')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('debit')
                    ->label('Débito')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('credit')
                    ->label('Crédito')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('closing_balance')
                    ->label('Saldo Final')
                    ->money('COP')
                    ->alignEnd()
                    ->weight('medium'),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Fecha')
                    ->schema([
                        DatePicker::make('starts_on')->label('Desde'),
                        DatePicker::make('ends_on')->label('Hasta'),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.general-ledger', $this->reportQueryParameters())),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay cuentas con movimiento')
            ->emptyStateDescription('Ajusta los filtros o registra comprobantes para generar el libro mayor.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function paginatedRows(?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator
    {
        $recordsPerPage = is_numeric($recordsPerPage) ? (int) $recordsPerPage : 25;
        $rows = $this->rows($filters);

        return new LengthAwarePaginator(
            items: $rows->forPage($page, $recordsPerPage)->values(),
            total: $rows->count(),
            perPage: $recordsPerPage,
            currentPage: $page,
        );
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(?array $filters = null): Collection
    {
        $date = $filters['date'] ?? [];

        return app(FinancialStatement::class)
            ->generalLedger(app(CurrentCompany::class)->get(), $date['starts_on'] ?? null, $date['ends_on'] ?? null)
            ->map(fn (array $row, int $index): array => ['id' => (string) ($index + 1), ...$row]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function reportQueryParameters(): array
    {
        $date = $this->tableFilters['date'] ?? [];

        return array_filter([
            'starts_on' => $date['starts_on'] ?? null,
            'ends_on' => $date['ends_on'] ?? null,
        ], filled(...));
    }
}

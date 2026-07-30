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

class IncomeStatementReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentChartBar;

    protected static ?string $navigationLabel = 'Estado de resultados';

    protected static ?string $title = 'Estado de Resultados';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.income-statement-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Ingresos, Gastos y Costos')
            ->description(fn (): string => $this->summaryDescription())
            ->columns([
                TextColumn::make('class_label')
                    ->label('Clase')
                    ->badge(),
                TextColumn::make('code')
                    ->label('Código')
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Cuenta'),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('COP')
                    ->alignEnd(),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Periodo')
                    ->schema([
                        DatePicker::make('starts_on')->label('Desde')->default(now()->startOfYear()->toDateString()),
                        DatePicker::make('ends_on')->label('Hasta')->default(now()->toDateString()),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.financial-statements', array_filter([
                        'starts_on' => $this->tableFilters['date']['starts_on'] ?? null,
                        'ends_on' => $this->tableFilters['date']['ends_on'] ?? null,
                    ], filled(...)))),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay movimientos en el periodo')
            ->emptyStateDescription('Ajusta el periodo o registra comprobantes de ingreso/gasto.');
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

        return app(FinancialStatement::class)->incomeStatementAccounts(
            app(CurrentCompany::class)->get(),
            $date['starts_on'] ?? null,
            $date['ends_on'] ?? null,
        );
    }

    protected function summaryDescription(): string
    {
        $date = $this->tableFilters['date'] ?? [];

        $statement = app(FinancialStatement::class)->incomeStatement(
            app(CurrentCompany::class)->get(),
            $date['starts_on'] ?? null,
            $date['ends_on'] ?? null,
        );

        return sprintf(
            'Ingresos: %s · Gastos y costos: %s · Resultado del ejercicio: %s',
            number_format($statement['totals']['ingresos'], 2),
            number_format($statement['totals']['gastos_costos'], 2),
            number_format($statement['net_income'], 2),
        );
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\BudgetAppropriation;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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

class BudgetExecutionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $navigationLabel = 'Ejecución Presupuestal';

    protected static ?string $title = 'Reporte de Ejecución Presupuestal';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.budget-execution-report';

    public static function canAccess(): bool
    {
        return app(CurrentCompany::class)->get()->has_budgetary_control;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Ejecución de Apropiaciones')
            ->description('Seguimiento en tiempo real de saldos, compromisos y obligaciones por rubro presupuestal.')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Rubro')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('appropriation')
                    ->label('Apropiación')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('cdps')
                    ->label('CDPs')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('rps')
                    ->label('RPs')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('obligated')
                    ->label('Obligado')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('percent')
                    ->label('% Ejecución')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('available')
                    ->label('Disponible')
                    ->money('COP')
                    ->alignEnd()
                    ->weight('medium')
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success'),
            ])
            ->filters([
                Filter::make('fiscal_year_filter')
                    ->form([
                        Select::make('fiscal_year')
                            ->label('Año Fiscal')
                            ->options(array_combine(
                                range(now()->year - 3, now()->year + 1),
                                range(now()->year - 3, now()->year + 1)
                            ))
                            ->default(now()->year)
                            ->selectablePlaceholder(false),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(fn () => $this->exportCsv()),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay apropiaciones registradas')
            ->emptyStateDescription('Ajusta los filtros o crea rubros presupuestales en la sección de Apropiaciones.');
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
        $fiscalYear = (int) ($filters['fiscal_year_filter']['fiscal_year'] ?? now()->year);
        $company = app(CurrentCompany::class)->get();

        $appropriations = BudgetAppropriation::query()
            ->whereBelongsTo($company)
            ->where('fiscal_year', $fiscalYear)
            ->where('is_active', true)
            ->with(['budgetCertificates.budgetRegistrations.budgetObligations'])
            ->get();

        return $appropriations->map(function (BudgetAppropriation $rubro, int $index): array {
            $cdpSum = $rubro->budgetCertificates->sum('amount');
            $rpSum = $rubro->budgetCertificates->flatMap->budgetRegistrations->sum('amount');
            $obligationSum = $rubro->budgetCertificates->flatMap->budgetRegistrations->flatMap->budgetObligations->sum('amount');

            $total = $rubro->total_appropriation;
            $percent = $total > 0 ? round(($obligationSum / $total) * 100, 1) : 0;
            $available = $rubro->available_amount;

            return [
                'id' => (string) ($index + 1),
                'code' => $rubro->code,
                'name' => $rubro->name,
                'appropriation' => (float) $total,
                'cdps' => (float) $cdpSum,
                'rps' => (float) $rpSum,
                'obligated' => (float) $obligationSum,
                'percent' => (float) $percent,
                'available' => (float) $available,
            ];
        });
    }

    public function exportCsv(): never
    {
        // Obtener el año fiscal actual del filtro
        $filters = $this->tableFilters;
        $fiscalYear = (int) ($filters['fiscal_year_filter']['fiscal_year'] ?? now()->year);
        $rows = $this->rows($this->tableFilters);

        $filename = "ejecucion_presupuestal_{$fiscalYear}.csv";
        $headers = ['Código', 'Rubro', 'Apropiación', 'CDPs', 'RPs', 'Obligado', '% Ejecución', 'Disponible'];

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            abort(500);
        }

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['code'],
                $row['name'],
                number_format((float) $row['appropriation'], 2, '.', ''),
                number_format((float) $row['cdps'], 2, '.', ''),
                number_format((float) $row['rps'], 2, '.', ''),
                number_format((float) $row['obligated'], 2, '.', ''),
                $row['percent'],
                number_format((float) $row['available'], 2, '.', ''),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        response($content !== false ? $content : '')
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename={$filename}")
            ->send();

        exit();
    }
}

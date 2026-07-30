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

class BalanceSheetReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Scale;

    protected static ?string $navigationLabel = 'Balance general';

    protected static ?string $title = 'Balance General';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.balance-sheet-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Activo, Pasivo y Patrimonio')
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
                Filter::make('cutoff')
                    ->label('Corte')
                    ->schema([
                        DatePicker::make('cutoff')->label('Corte al')->default(now()->toDateString()),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.financial-statements', array_filter([
                        'ends_on' => $this->tableFilters['cutoff']['cutoff'] ?? null,
                    ], filled(...)))),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay cuentas con saldo')
            ->emptyStateDescription('Registra comprobantes para ver el balance general.');
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
        return app(FinancialStatement::class)->balanceSheetAccounts(
            app(CurrentCompany::class)->get(),
            $filters['cutoff']['cutoff'] ?? null,
        );
    }

    protected function summaryDescription(): string
    {
        $statement = app(FinancialStatement::class)->balanceSheet(
            app(CurrentCompany::class)->get(),
            $this->tableFilters['cutoff']['cutoff'] ?? null,
        );

        $status = $statement['is_balanced'] ? 'Cuadra' : 'No cuadra';

        return sprintf(
            'Activo: %s · Pasivo: %s · Patrimonio: %s · Resultado del ejercicio: %s · %s (Activo = Pasivo + Patrimonio + Resultado)',
            number_format($statement['totals']['activo'], 2),
            number_format($statement['totals']['pasivo'], 2),
            number_format($statement['totals']['patrimonio'], 2),
            number_format($statement['net_income'], 2),
            $status,
        );
    }
}

<?php

namespace App\Filament\Pages;

use App\Services\Accounting\CurrentCompany;
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
use Illuminate\Support\Facades\DB;

class TrialBalanceReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $navigationLabel = 'Balance';

    protected static ?string $title = 'Balance de comprobación';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.trial-balance-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Balance por cuenta')
            ->description('Resumen de débitos, créditos y saldo por cuenta PUC.')
            ->columns([
                TextColumn::make('code')
                    ->label('Cuenta')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('debit_total')
                    ->label('Débito')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('credit_total')
                    ->label('Crédito')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('COP')
                    ->alignEnd(),
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
                    ->url(fn (): string => route('accounting-reports.trial-balance', array_merge($this->reportQueryParameters(), ['export' => 1]))),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay cuentas con movimiento')
            ->emptyStateDescription('Ajusta los filtros o registra comprobantes para generar el balance.');
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
        $company = app(CurrentCompany::class)->get();

        return DB::table('accounting_entries')
            ->select('chart_accounts.code', 'chart_accounts.name', DB::raw('sum(accounting_entries.debit) as debit_total'), DB::raw('sum(accounting_entries.credit) as credit_total'))
            ->join('chart_accounts', 'chart_accounts.id', '=', 'accounting_entries.chart_account_id')
            ->join('vouchers', 'vouchers.id', '=', 'accounting_entries.voucher_id')
            ->where('vouchers.company_id', $company->id)
            ->when($date['starts_on'] ?? null, fn ($query, $date) => $query->whereDate('vouchers.date', '>=', $date))
            ->when($date['ends_on'] ?? null, fn ($query, $date) => $query->whereDate('vouchers.date', '<=', $date))
            ->groupBy('chart_accounts.id', 'chart_accounts.code', 'chart_accounts.name')
            ->orderBy('chart_accounts.code')
            ->get()
            ->map(fn (object $row, int $index): array => [
                'id' => (string) ($index + 1),
                'code' => $row->code,
                'name' => $row->name,
                'debit_total' => (float) $row->debit_total,
                'credit_total' => (float) $row->credit_total,
                'balance' => (float) $row->debit_total - (float) $row->credit_total,
            ]);
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

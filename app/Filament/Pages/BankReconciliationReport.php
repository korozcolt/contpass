<?php

namespace App\Filament\Pages;

use App\Models\CashAccount;
use App\Services\Accounting\BankReconciliation;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BankReconciliationReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;

    protected static ?string $navigationLabel = 'Conciliación bancaria';

    protected static ?string $title = 'Conciliación bancaria';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.bank-reconciliation-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Partidas pendientes de conciliar')
            ->description(fn (): string => $this->summaryDescription())
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('Y-m-d'),
                TextColumn::make('voucher_number')
                    ->label('Comprobante')
                    ->fontFamily('mono'),
                TextColumn::make('third_party')
                    ->label('Tercero')
                    ->placeholder('Sin tercero'),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->placeholder('—'),
                TextColumn::make('signed_amount')
                    ->label('Monto')
                    ->money('COP')
                    ->alignEnd(),
                IconColumn::make('reconciled')
                    ->label('Conciliado')
                    ->boolean(),
            ])
            ->filters([
                Filter::make('cash_account')
                    ->form([
                        Select::make('value')
                            ->label('Caja / Banco')
                            ->options(fn (): array => $this->cashAccounts()->pluck('name', 'id')->all())
                            ->default(fn (): ?int => $this->cashAccounts()->first()?->id)
                            ->searchable(),
                    ]),
                Filter::make('cutoff')
                    ->form([
                        DatePicker::make('value')->label('Fecha de corte'),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.bank-reconciliation', $this->reportQueryParameters())),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Todo conciliado')
            ->emptyStateDescription('No hay pagos pendientes de conciliar para esta cuenta. Marca pagos como conciliados desde el listado de Pagos.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function cashAccounts()
    {
        return CashAccount::query()
            ->whereBelongsTo(app(CurrentCompany::class)->get())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
        $cashAccount = $this->selectedCashAccount($filters);

        if ($cashAccount === null) {
            return collect();
        }

        $cutoff = $filters['cutoff']['value'] ?? null;

        return app(BankReconciliation::class)->pendingItems(app(CurrentCompany::class)->get(), $cashAccount, $cutoff);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function selectedCashAccount(?array $filters): ?CashAccount
    {
        $cashAccountId = $filters['cash_account']['value'] ?? null;

        if ($cashAccountId) {
            return $this->cashAccounts()->firstWhere('id', (int) $cashAccountId);
        }

        return $this->cashAccounts()->first();
    }

    protected function summaryDescription(): string
    {
        $cashAccount = $this->selectedCashAccount($this->tableFilters);

        if ($cashAccount === null) {
            return 'Crea una cuenta de caja o banco para poder conciliar.';
        }

        $cutoff = $this->tableFilters['cutoff']['value'] ?? null;
        $summary = app(BankReconciliation::class)->summary(app(CurrentCompany::class)->get(), $cashAccount, $cutoff);

        return sprintf(
            '%s — Saldo en libros: %s · Conciliado: %s · Pendiente: %s',
            $cashAccount->name,
            number_format($summary['book_balance'], 2),
            number_format($summary['reconciled_balance'], 2),
            number_format($summary['pending_balance'], 2),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function reportQueryParameters(): array
    {
        return array_filter([
            'cash_account_id' => $this->tableFilters['cash_account']['value'] ?? $this->cashAccounts()->first()?->id,
            'cutoff' => $this->tableFilters['cutoff']['value'] ?? null,
        ], filled(...));
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\ThirdParty;
use App\Services\Accounting\AccountsReceivable;
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

class AccountsReceivableReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $navigationLabel = 'Cartera de clientes';

    protected static ?string $title = 'Cartera de Clientes';

    protected static string|\UnitEnum|null $navigationGroup = 'Cuentas x Cobrar';

    protected string $view = 'filament.pages.accounts-receivable-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Comprobantes de ingreso pendientes de pago')
            ->description(fn (): string => $this->summaryDescription())
            ->columns([
                TextColumn::make('third_party')
                    ->label('Tercero')
                    ->searchable(),
                TextColumn::make('voucher_number')
                    ->label('Comprobante')
                    ->fontFamily('mono'),
                TextColumn::make('support_number')
                    ->label('Soporte'),
                TextColumn::make('accrual_date')
                    ->label('Fecha')
                    ->date('Y-m-d'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('paid')
                    ->label('Pagado')
                    ->money('COP')
                    ->alignEnd(),
                TextColumn::make('pending')
                    ->label('Saldo')
                    ->money('COP')
                    ->alignEnd()
                    ->weight('medium'),
                TextColumn::make('days_overdue')
                    ->label('Días')
                    ->alignCenter(),
                TextColumn::make('bucket')
                    ->label('Edad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Corriente' => 'success',
                        '31-60 días' => 'warning',
                        '61-90 días' => 'danger',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Filter::make('third_party')
                    ->form([
                        Select::make('value')
                            ->label('Tercero')
                            ->options(fn (): array => $this->thirdParties()->pluck('name', 'name')->all())
                            ->searchable(),
                    ]),
                Filter::make('bucket')
                    ->form([
                        Select::make('value')
                            ->label('Edad de cartera')
                            ->options([
                                'Corriente' => 'Corriente',
                                '31-60 días' => '31-60 días',
                                '61-90 días' => '61-90 días',
                                '+90 días' => '+90 días',
                            ]),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.accounts-receivable')),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay cartera pendiente')
            ->emptyStateDescription('Todos los comprobantes de ingreso registrados ya fueron pagados.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function thirdParties()
    {
        return ThirdParty::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('name')->get();
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
        $rows = app(AccountsReceivable::class)->openItems(app(CurrentCompany::class)->get());

        $thirdParty = $filters['third_party']['value'] ?? null;
        $bucket = $filters['bucket']['value'] ?? null;

        return $rows
            ->when($thirdParty, fn (Collection $rows, string $thirdParty): Collection => $rows->where('third_party', $thirdParty))
            ->when($bucket, fn (Collection $rows, string $bucket): Collection => $rows->where('bucket', $bucket))
            ->map(fn (array $row, int $index): array => ['id' => (string) ($index + 1), ...$row])
            ->values();
    }

    protected function summaryDescription(): string
    {
        $rows = app(AccountsReceivable::class)->openItems(app(CurrentCompany::class)->get());

        $totalPending = $rows->sum('pending');
        $overdue = $rows->filter(fn (array $row): bool => $row['bucket'] !== 'Corriente')->sum('pending');

        return sprintf(
            'Cartera total pendiente: %s · Cartera vencida (más de 30 días): %s',
            number_format($totalPending, 2),
            number_format($overdue, 2),
        );
    }
}

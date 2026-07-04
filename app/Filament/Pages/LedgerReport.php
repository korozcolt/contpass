<?php

namespace App\Filament\Pages;

use App\Models\AccountingEntry;
use App\Models\ChartAccount;
use App\Models\ThirdParty;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LedgerReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static ?string $navigationLabel = 'Libro auxiliar';

    protected static ?string $title = 'Libro auxiliar';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.ledger-report';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entriesQuery())
            ->heading('Movimientos contables')
            ->description('Libro auxiliar por fecha, cuenta PUC y tercero.')
            ->columns([
                TextColumn::make('voucher.date')
                    ->label('Fecha')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('voucher.number')
                    ->label('Comprobante')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('chartAccount.full_name')
                    ->label('Cuenta PUC')
                    ->wrap()
                    ->searchable(['chart_accounts.code', 'chart_accounts.name']),
                TextColumn::make('thirdParty.name')
                    ->label('Tercero')
                    ->placeholder('Sin tercero')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('Sin descripción')
                    ->limit(60)
                    ->tooltip(fn (AccountingEntry $record): ?string => $record->description),
                TextColumn::make('debit')
                    ->label('Débito')
                    ->money('COP')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('credit')
                    ->label('Crédito')
                    ->money('COP')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Fecha')
                    ->schema([
                        DatePicker::make('starts_on')->label('Desde'),
                        DatePicker::make('ends_on')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['starts_on'] ?? null, fn (Builder $query, string $date): Builder => $query->whereHas('voucher', fn (Builder $query): Builder => $query->whereDate('date', '>=', $date)))
                        ->when($data['ends_on'] ?? null, fn (Builder $query, string $date): Builder => $query->whereHas('voucher', fn (Builder $query): Builder => $query->whereDate('date', '<=', $date)))),
                SelectFilter::make('chart_account_id')
                    ->label('Cuenta PUC')
                    ->options(fn (): array => $this->chartAccounts()->pluck('full_name', 'id')->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('third_party_id')
                    ->label('Tercero')
                    ->options(fn (): array => $this->thirdParties()->mapWithKeys(fn (ThirdParty $thirdParty): array => [$thirdParty->id => "{$thirdParty->tax_id} · {$thirdParty->name}"])->all())
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.ledger', array_merge($this->reportQueryParameters(), ['export' => 1]))),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay movimientos')
            ->emptyStateDescription('Ajusta los filtros o registra comprobantes para ver información en este reporte.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function chartAccounts()
    {
        return ChartAccount::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('code')->get();
    }

    public function thirdParties()
    {
        return ThirdParty::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('name')->get();
    }

    protected function entriesQuery(): Builder
    {
        return AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo(app(CurrentCompany::class)->get()));
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
            'chart_account_id' => $this->tableFilters['chart_account_id']['value'] ?? null,
            'third_party_id' => $this->tableFilters['third_party_id']['value'] ?? null,
        ], filled(...));
    }
}

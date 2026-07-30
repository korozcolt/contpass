<?php

namespace App\Filament\Pages;

use App\Enums\VoucherType;
use App\Models\AccountingEntry;
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
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $navigationLabel = 'Libro diario';

    protected static ?string $title = 'Libro diario';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.journal-report';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entriesQuery())
            ->heading('Comprobantes en orden cronológico')
            ->description('Libro diario: asientos agrupados por comprobante, en el orden en que se contabilizaron.')
            ->groups([
                Group::make('voucher.number')
                    ->label('Comprobante')
                    ->getTitleFromRecordUsing(fn (AccountingEntry $record): string => "{$record->voucher->number} · {$record->voucher->date->format('Y-m-d')} · {$record->voucher->type->getLabel()}")
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query->orderBy('vouchers.date', $direction)->orderBy('vouchers.number', $direction)),
            ])
            ->defaultGroup('voucher.number')
            ->columns([
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
                    ->alignEnd(),
                TextColumn::make('credit')
                    ->label('Crédito')
                    ->money('COP')
                    ->alignEnd(),
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
                SelectFilter::make('type')
                    ->label('Tipo de comprobante')
                    ->options(fn (): array => collect(VoucherType::cases())->mapWithKeys(fn (VoucherType $type): array => [$type->value => $type->getLabel()])->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $query, string $type): Builder => $query->whereHas('voucher', fn (Builder $query): Builder => $query->where('type', $type)))),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export')
                    ->label('Exportar CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (): string => route('accounting-reports.journal', $this->reportQueryParameters())),
            ])
            ->defaultSort('id')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay comprobantes')
            ->emptyStateDescription('Ajusta los filtros o registra comprobantes para ver información en este reporte.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function entriesQuery(): Builder
    {
        return AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereHas('voucher', fn (Builder $query): Builder => $query->whereBelongsTo(app(CurrentCompany::class)->get()))
            ->join('vouchers', 'vouchers.id', '=', 'accounting_entries.voucher_id')
            ->orderBy('vouchers.date')
            ->orderBy('vouchers.number')
            ->select('accounting_entries.*');
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
            'type' => $this->tableFilters['type']['value'] ?? null,
        ], filled(...));
    }
}

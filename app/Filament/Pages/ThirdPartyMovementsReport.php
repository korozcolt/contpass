<?php

namespace App\Filament\Pages;

use App\Models\AccountingEntry;
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

class ThirdPartyMovementsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $navigationLabel = 'Movimientos por tercero';

    protected static ?string $title = 'Movimientos por tercero';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected string $view = 'filament.pages.third-party-movements-report';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entriesQuery())
            ->heading('Movimientos por tercero')
            ->description('Consulta débitos y créditos asociados a clientes, proveedores y terceros.')
            ->columns([
                TextColumn::make('voucher.date')
                    ->label('Fecha')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('thirdParty.name')
                    ->label('Tercero')
                    ->searchable(),
                TextColumn::make('voucher.number')
                    ->label('Comprobante')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('chartAccount.full_name')
                    ->label('Cuenta PUC')
                    ->wrap()
                    ->searchable(['chart_accounts.code', 'chart_accounts.name']),
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
                    ->url(fn (): string => route('accounting-reports.third-party-movements', array_merge($this->reportQueryParameters(), ['export' => 1]))),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay movimientos por tercero')
            ->emptyStateDescription('Ajusta los filtros o registra movimientos con tercero asociado.');
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

    protected function entriesQuery(): Builder
    {
        return AccountingEntry::query()
            ->with(['voucher', 'chartAccount', 'thirdParty'])
            ->whereNotNull('third_party_id')
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
            'third_party_id' => $this->tableFilters['third_party_id']['value'] ?? null,
        ], filled(...));
    }
}

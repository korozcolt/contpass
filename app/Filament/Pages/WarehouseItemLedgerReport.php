<?php

namespace App\Filament\Pages;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovementLine;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
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

class WarehouseItemLedgerReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static ?string $navigationLabel = 'Auxiliar de elementos';

    protected static ?string $title = 'Auxiliar de Elementos';

    protected static string|\UnitEnum|null $navigationGroup = 'Almacén';

    protected string $view = 'filament.pages.warehouse-item-ledger-report';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->linesQuery())
            ->heading('Movimientos por elemento')
            ->description('Kardex: entradas, salidas, traslados y bajas por elemento.')
            ->columns([
                TextColumn::make('warehouseMovement.date')
                    ->label('Fecha')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('warehouseMovement.number')
                    ->label('Documento')
                    ->fontFamily('mono'),
                TextColumn::make('warehouseMovement.type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('warehouseItem.full_name')
                    ->label('Elemento')
                    ->wrap()
                    ->searchable(['warehouse_items.code', 'warehouse_items.name']),
                TextColumn::make('warehouseMovement.warehouse.name')
                    ->label('Almacén'),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric(decimalPlaces: 2)
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
                        ->when($data['starts_on'] ?? null, fn (Builder $query, string $date): Builder => $query->whereHas('warehouseMovement', fn (Builder $query): Builder => $query->whereDate('date', '>=', $date)))
                        ->when($data['ends_on'] ?? null, fn (Builder $query, string $date): Builder => $query->whereHas('warehouseMovement', fn (Builder $query): Builder => $query->whereDate('date', '<=', $date)))),
                SelectFilter::make('warehouse_item_id')
                    ->label('Elemento')
                    ->options(fn (): array => $this->warehouseItems()->mapWithKeys(fn (WarehouseItem $item): array => [$item->id => $item->full_name])->all())
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay movimientos')
            ->emptyStateDescription('Ajusta los filtros o registra movimientos de almacén.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function warehouseItems()
    {
        return WarehouseItem::query()->whereBelongsTo(app(CurrentCompany::class)->get())->orderBy('code')->get();
    }

    protected function linesQuery(): Builder
    {
        return WarehouseMovementLine::query()
            ->with(['warehouseMovement.warehouse', 'warehouseItem'])
            ->whereHas('warehouseMovement', fn (Builder $query): Builder => $query->whereBelongsTo(app(CurrentCompany::class)->get()));
    }
}

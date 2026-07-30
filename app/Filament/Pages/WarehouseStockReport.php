<?php

namespace App\Filament\Pages;

use App\Enums\WarehouseItemType;
use App\Models\WarehouseItem;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WarehouseStockReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static ?string $navigationLabel = 'Stock de elementos';

    protected static ?string $title = 'Stock de Elementos';

    protected static string|\UnitEnum|null $navigationGroup = 'Almacén';

    protected string $view = 'filament.pages.warehouse-stock-report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Saldo actual por elemento')
            ->description('Suma de entradas menos salidas y bajas por elemento, en toda la compañía.')
            ->columns([
                TextColumn::make('code')->label('Código')->fontFamily('mono')->searchable(),
                TextColumn::make('name')->label('Nombre')->searchable()->wrap(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('unit_of_measure')->label('UM'),
                TextColumn::make('current_stock')
                    ->label('Stock actual')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->color(fn ($record): string => $record['below_minimum'] ? 'danger' : 'success'),
                TextColumn::make('minimum_stock')->label('Stock mínimo')->numeric(decimalPlaces: 2)->alignEnd()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(WarehouseItemType::class),
            ], layout: FiltersLayout::AboveContent)
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay elementos registrados')
            ->emptyStateDescription('Crea elementos en Catálogos → Elementos para ver su stock aquí.');
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
        $type = $filters['type']['value'] ?? null;

        return WarehouseItem::query()
            ->whereBelongsTo(app(CurrentCompany::class)->get())
            ->when($type, fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('code')
            ->get()
            ->map(fn (WarehouseItem $item): array => [
                'id' => (string) $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->type,
                'unit_of_measure' => $item->unit_of_measure,
                'current_stock' => $item->current_stock,
                'minimum_stock' => $item->minimum_stock,
                'below_minimum' => $item->minimum_stock !== null && $item->current_stock < (float) $item->minimum_stock,
            ]);
    }
}

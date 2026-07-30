<?php

namespace App\Filament\Resources\WarehouseMovements\RelationManagers;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Services\Accounting\CurrentCompany;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $title = 'Elementos del movimiento';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('warehouse_item_id')
                    ->label('Elemento')
                    ->options(fn (): array => WarehouseItem::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn (WarehouseItem $item): array => [$item->id => $item->full_name])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->step('0.01')
                    ->minValue(0.01)
                    ->maxValue(function (Get $get): ?float {
                        if (! $this->ownerRecordDecreasesStock()) {
                            return null;
                        }

                        $item = WarehouseItem::find($get('warehouse_item_id'));

                        return $item?->current_stock;
                    })
                    ->helperText(function (Get $get): ?string {
                        if (! $this->ownerRecordDecreasesStock()) {
                            return null;
                        }

                        $item = WarehouseItem::find($get('warehouse_item_id'));

                        return $item ? "Stock disponible: {$item->current_stock} {$item->unit_of_measure}" : null;
                    })
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Costo unitario')
                    ->numeric()
                    ->step('0.01')
                    ->minValue(0)
                    ->prefix('COP $'),
                TextInput::make('asset_tag')
                    ->label('Placa / referencia individual')
                    ->maxLength(50)
                    ->helperText('Opcional, para elementos devolutivos.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse_item_id')
            ->columns([
                TextColumn::make('warehouseItem.full_name')->label('Elemento'),
                TextColumn::make('quantity')->label('Cantidad')->numeric(decimalPlaces: 2)->alignEnd(),
                TextColumn::make('unit_cost')->label('Costo unitario')->money('COP')->alignEnd()->placeholder('—'),
                TextColumn::make('asset_tag')->label('Placa')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar elemento')
                    ->icon(Heroicon::PlusCircle),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    private function ownerRecordDecreasesStock(): bool
    {
        /** @var WarehouseMovement $owner */
        $owner = $this->getOwnerRecord();

        return $owner->type->decreasesStock();
    }
}

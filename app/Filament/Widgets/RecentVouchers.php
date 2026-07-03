<?php

namespace App\Filament\Widgets;

use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentVouchers extends TableWidget
{
    protected static ?string $heading = 'Comprobantes recientes';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Voucher::query()
                ->with('thirdParty')
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->latest())
            ->columns([
                TextColumn::make('number')->label('Número')->searchable(),
                TextColumn::make('date')->label('Fecha')->date()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('thirdParty.name')->label('Tercero')->placeholder('-'),
                TextColumn::make('description')->label('Descripción')->limit(50),
            ]);
    }
}

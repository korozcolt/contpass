<?php

namespace App\Filament\Widgets;

use App\Models\Voucher;
use App\Services\Accounting\CurrentCompany;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RecentVouchers extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Actividad contable reciente';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Voucher::query()
                ->with('thirdParty')
                ->withSum('entries as debit_total', 'debit')
                ->whereBelongsTo(app(CurrentCompany::class)->get())
                ->latest('date')
                ->latest('id'))
            ->description('Últimos comprobantes registrados para revisar causación, aprobación y trazabilidad.')
            ->columns([
                TextColumn::make('number')
                    ->label('Comprobante')
                    ->description(fn (Voucher $record): string => collect([
                        $record->date?->format('d/m/Y'),
                        $record->type->getLabel(),
                        $record->thirdParty?->name,
                        filled($record->description) ? Str::limit($record->description, 48) : null,
                    ])->filter()->join(' - '))
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('debit_total')
                    ->label('Valor')
                    ->money('COP', locale: 'es_CO')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->emptyStateIcon(Heroicon::OutlinedDocumentChartBar)
            ->emptyStateHeading('Aún no hay comprobantes')
            ->emptyStateDescription('Cuando se causen ingresos, egresos, pagos o ajustes aparecerán aquí.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}

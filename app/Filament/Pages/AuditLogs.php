<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\User;
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
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class AuditLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?string $navigationLabel = 'Pista de Auditoría';

    protected static ?string $title = 'Historial de Auditoría';

    protected static string|\UnitEnum|null $navigationGroup = 'Control';

    protected string $view = 'filament.pages.audit-logs';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($filters, $page, $recordsPerPage))
            ->heading('Pistas de Auditoría Recientes')
            ->description('Logs estructurados de operaciones críticas sobre presupuestos, cuentas contables y comprobantes.')
            ->columns([
                TextColumn::make('timestamp')
                    ->label('Fecha / Hora')
                    ->dateTime('Y-m-d H:i:s')
                    ->fontFamily('mono')
                    ->sortable(),
                TextColumn::make('user_name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder('Sistema / Anónimo'),
                TextColumn::make('event')
                    ->label('Operación')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Creado',
                        'updated' => 'Modificado',
                        'deleted' => 'Eliminado',
                        default => $state,
                    })
                    ->alignCenter(),
                TextColumn::make('friendly_model')
                    ->label('Módulo / Entidad')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('model_id')
                    ->label('ID Registro')
                    ->fontFamily('mono'),
                TextColumn::make('changes_summary')
                    ->label('Valores Nuevos / Modificados')
                    ->wrap()
                    ->limit(100),
                TextColumn::make('ip_address')
                    ->label('Dirección IP')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')
                    ->label('Navegador / Agente')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('log_date_filter')
                    ->form([
                        DatePicker::make('date')
                            ->label('Fecha de Auditoría')
                            ->default(now()->toDateString()),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay registros de auditoría')
            ->emptyStateDescription('Ajusta el filtro de fecha o realiza operaciones sobre los rubros y comprobantes para ver registros.');
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
        $date = $filters['log_date_filter']['date'] ?? now()->toDateString();
        $filePath = storage_path("logs/audit/contpass_audit-{$date}.log");

        if (! File::exists($filePath)) {
            return collect();
        }

        // Cargar los últimos 500 logs leídos en reversa (más recientes primero)
        $lines = $this->readLogFileBackwards($filePath, 500);
        $parsedLogs = [];

        // Cache de nombres de usuarios para evitar N+1 queries sobre los logs
        $userNames = [];

        $logCount = 0;
        foreach ($lines as $line) {
            // Un log de Monolog luce así:
            // [2026-07-06T10:15:00-05:00] production.INFO: Audit transaction recorded {"event":"created", ...} []
            $pattern = '/^\[(?P<timestamp>.+?)\]\s+\S+\.INFO:\s+Audit transaction recorded\s+(?P<context>\{.*\})(?:\s+\[.*\])?\s*$/';
            if (preg_match($pattern, trim($line), $matches)) {
                $context = json_decode($matches['context'], true);
                if (! $context) {
                    continue;
                }

                $userId = $context['user_id'] ?? null;
                $userName = null;
                if ($userId) {
                    if (! isset($userNames[$userId])) {
                        $userNames[$userId] = User::query()->where('id', $userId)->value('name');
                    }
                    $userName = $userNames[$userId];
                }

                $parsedLogs[] = [
                    'id' => (string) (++$logCount),
                    'timestamp' => $context['timestamp'] ?? $matches['timestamp'],
                    'user_name' => $userName,
                    'event' => $context['event'] ?? 'unknown',
                    'friendly_model' => $this->getFriendlyModelName($context['model_type'] ?? ''),
                    'model_id' => $context['model_id'] ?? '',
                    'changes_summary' => $this->formatChangesSummary($context['event'] ?? '', $context['new_values'] ?? [], $context['old_values'] ?? []),
                    'ip_address' => $context['ip_address'] ?? '',
                    'user_agent' => $context['user_agent'] ?? '',
                ];
            }
        }

        return collect($parsedLogs);
    }

    /**
     * Lee un archivo de texto de atrás hacia adelante de forma eficiente sin cargar todo a memoria.
     *
     * @return array<int, string>
     */
    protected function readLogFileBackwards(string $filePath, int $limit = 500): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [];
        }

        $lines = [];
        $line = '';

        fseek($handle, 0, SEEK_END);
        $pos = ftell($handle);

        while ($pos > 0 && count($lines) < $limit) {
            fseek($handle, --$pos);
            $char = fgetc($handle);

            if ($char === "\n") {
                if ($line !== '') {
                    $lines[] = strrev($line);
                    $line = '';
                }
            } else {
                $line .= $char;
            }
        }

        if ($line !== '') {
            $lines[] = strrev($line);
        }

        fclose($handle);

        return $lines;
    }

    protected function getFriendlyModelName(string $modelClass): string
    {
        $parts = explode('\\', $modelClass);
        $baseName = end($parts);

        return match ($baseName) {
            'BudgetAppropriation' => 'Apropiación',
            'BudgetAvailabilityCertificate' => 'CDP',
            'BudgetRegistration' => 'RP',
            'BudgetObligation' => 'Obligación',
            'PaymentOrder' => 'Orden de Pago',
            'Voucher' => 'Comprobante',
            default => $baseName ?: 'Entidad',
        };
    }

    protected function formatChangesSummary(string $event, array $new, array $old): string
    {
        if ($event === 'created') {
            // Filtrar campos de control internos para no saturar la vista
            $filtered = array_filter($new, fn ($k) => ! in_array($k, ['id', 'created_at', 'updated_at', 'company_id']), ARRAY_FILTER_USE_KEY);

            return json_encode($filtered, JSON_UNESCAPED_UNICODE) ?: '';
        }

        if ($event === 'updated') {
            $summary = [];
            foreach ($new as $key => $value) {
                if (in_array($key, ['updated_at'])) {
                    continue;
                }
                $oldValue = $old[$key] ?? 'N/A';

                $oldStr = is_array($oldValue) || is_object($oldValue)
                    ? json_encode($oldValue, JSON_UNESCAPED_UNICODE)
                    : (string) $oldValue;

                $newStr = is_array($value) || is_object($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string) $value;

                $summary[$key] = "{$oldStr} ➔ {$newStr}";
            }

            return json_encode($summary, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return '';
    }
}

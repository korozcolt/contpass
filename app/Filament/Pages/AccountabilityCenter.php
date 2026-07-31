<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccountabilityCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Rendición de cuentas';

    protected static ?string $title = 'Rendición de cuentas';

    protected static string|\UnitEnum|null $navigationGroup = 'Rendición';

    protected string $view = 'filament.pages.accountability-center';

    /**
     * @return array<int, array{title: string, description: string, items: array<int, string>}>
     */
    public function obligations(): array
    {
        return [
            [
                'title' => 'Contaduría General de la Nación (CHIP)',
                'description' => 'Balance consolidado y operaciones recíprocas que se reportan al Contador General de la Nación a través del CHIP.',
                'items' => [
                    'Generar Balance CGN',
                    'Vincular operaciones recíprocas con otras entidades públicas',
                    'Generar operaciones recíprocas',
                ],
            ],
            [
                'title' => 'Contraloría — Sistema Integral de Auditoría (SIA)',
                'description' => 'Homologación del plan de cuentas propio al catálogo de la Contraloría y generación de los formatos que audita ese organismo.',
                'items' => [
                    'Homologar rubros al catálogo de la Contraloría',
                    'Generar formatos SIA',
                ],
            ],
            [
                'title' => 'Contraloría General',
                'description' => 'Formatos de rendición de cuentas exigidos directamente por la Contraloría General.',
                'items' => [
                    'Generar formatos de rendición de cuentas',
                ],
            ],
            [
                'title' => 'Medios Magnéticos / Exógena DIAN',
                'description' => 'Información exógena tributaria que se reporta anualmente a la DIAN.',
                'items' => [
                    'Homologar conceptos tributarios',
                    'Formato 1001 — Pagos y retenciones practicadas',
                    'Formato 1009 — Ingresos recibidos',
                    'Formato 2276 — Información exógena de contratos',
                ],
            ],
        ];
    }

    public function content(Schema $schema): Schema
    {
        $company = app(CurrentCompany::class)->get();

        return $schema->components([
            Text::make($this->entityContextLabel($company))
                ->color('gray')
                ->weight('bold'),
            Text::make('Este módulo documenta las obligaciones de reporte a entes de control identificadas frente a Apolo. Todavía no genera los formatos oficiales: eso requiere validar cada especificación exacta contra la entidad de control correspondiente. Cuáles obligaciones aplican exactamente según el tipo de entidad tampoco está automatizado todavía — es un paso futuro una vez se confirme esa regla. Por ahora, deja explícito el alcance legal pendiente para que no se pierda de vista.')
                ->color('gray'),
            ...collect($this->obligations())->map(fn (array $obligation): Section => Section::make($obligation['title'])
                ->description($obligation['description'])
                ->schema([
                    Text::make('Pendiente de implementar')
                        ->badge()
                        ->color('gray')
                        ->icon(Heroicon::Clock),
                    UnorderedList::make($obligation['items']),
                ]))->all(),
        ]);
    }

    private function entityContextLabel(Company $company): string
    {
        $naturaleza = $company->type->getLabel();

        if ($company->public_entity_type === null) {
            return "{$company->name} · {$naturaleza}";
        }

        return "{$company->name} · {$naturaleza} · {$company->public_entity_type->getLabel()}";
    }
}

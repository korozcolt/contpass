<?php

namespace App\Filament\Pages;

use App\Enums\CompanyType;
use App\Models\Company;
use App\Services\Accounting\CurrentCompany;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    protected static ?string $navigationLabel = 'Datos de la Empresa';

    protected static ?string $title = 'Configuración de la Empresa';

    protected static string|\UnitEnum|null $navigationGroup = 'Control';

    protected string $view = 'filament.pages.company-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public Company $company;

    public function mount(): void
    {
        $this->company = app(CurrentCompany::class)->get();

        $this->form->fill($this->company->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identificación Legal')
                    ->description('Datos de registro legal e identificación tributaria.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo de Entidad')
                                    ->options(CompanyType::class)
                                    ->required()
                                    ->native(false),
                                TextInput::make('name')
                                    ->label('Nombre / Razón Social')
                                    ->required()
                                    ->maxLength(255),
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('tax_id')
                                            ->label('NIT')
                                            ->required()
                                            ->maxLength(20)
                                            ->columnSpan(3),
                                        TextInput::make('verification_digit')
                                            ->label('DV')
                                            ->numeric()
                                            ->maxLength(1)
                                            ->columnSpan(1),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Contacto y Ubicación')
                    ->description('Datos de ubicación física y contacto institucional.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address')
                                    ->label('Dirección')
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->label('Ciudad')
                                    ->maxLength(100),
                                TextInput::make('phone')
                                    ->label('Teléfono de Contacto')
                                    ->tel()
                                    ->maxLength(50),
                                TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->maxLength(150),
                                TextInput::make('legal_representative')
                                    ->label('Representante Legal')
                                    ->maxLength(150)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Parámetros del Sistema')
                    ->description('Configuraciones generales del panel.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('currency')
                                    ->label('Moneda')
                                    ->options([
                                        'COP' => 'Peso Colombiano (COP)',
                                        'USD' => 'Dólar Americano (USD)',
                                    ])
                                    ->default('COP')
                                    ->required()
                                    ->native(false),
                                Toggle::make('has_budgetary_control')
                                    ->label('Habilitar Control Presupuestal')
                                    ->helperText('Activa el módulo CDP, RP, Obligaciones y Reporte Presupuestal en el panel.')
                                    ->inline(false),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $this->company->update(
            $this->form->getState()
        );

        Notification::make()
            ->title('Datos de la empresa actualizados')
            ->success()
            ->send();
    }
}

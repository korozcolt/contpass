<?php

namespace App\Filament\Pages;

use App\Enums\BankProfile;
use App\Models\BankStatementImport;
use App\Models\CashAccount;
use App\Services\Accounting\CurrentCompany;
use App\Services\Accounting\ImportBankStatement;
use App\Services\Accounting\ProposeBankStatementMatches;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsList;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadBankStatement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    protected static ?string $navigationLabel = 'Importar Extracto Bancario';

    protected static ?string $title = 'Importar Extracto Bancario';

    protected static string|\UnitEnum|null $navigationGroup = 'Tesorería';

    protected string $view = 'filament.pages.upload-bank-statement';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('cash_account_id')
                    ->label('Cuenta bancaria')
                    ->options(fn (): array => CashAccount::query()
                        ->whereBelongsTo(app(CurrentCompany::class)->get())
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('bank')
                    ->label('Banco')
                    ->options(BankProfile::class)
                    ->required()
                    ->native(false),
                FileUpload::make('file')
                    ->label('Archivo CSV')
                    ->disk('local')
                    ->directory('bank-statements')
                    ->visibility('private')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('import')
                ->label('Importar extracto')
                ->color('primary')
                ->submit('import'),
        ];
    }

    public function import(): void
    {
        $data = $this->form->getState();

        $cashAccount = CashAccount::findOrFail($data['cash_account_id']);
        $bank = $data['bank'] instanceof BankProfile ? $data['bank'] : BankProfile::from($data['bank']);
        $absolutePath = Storage::disk('local')->path($data['file']);

        try {
            $import = app(ImportBankStatement::class)->handle(
                app(CurrentCompany::class)->get(),
                $cashAccount,
                $bank,
                $absolutePath,
            );
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'data.file' => $e->validator->errors()->first('file'),
            ]);
        }

        app(ProposeBankStatementMatches::class)->handle($import);

        $this->redirect(BankStatementReview::getUrl(['import' => $import->id]));
    }

    public function content(Schema $schema): Schema
    {
        $imports = BankStatementImport::query()
            ->with('cashAccount')
            ->latest()
            ->limit(10)
            ->get();

        return $schema->components([
            Section::make('Imports recientes')
                ->description('Extractos importados recientemente. Selecciona uno para continuar la revisión.')
                ->schema([
                    ActionsList::make(
                        $imports->map(fn (BankStatementImport $import): Action => Action::make("view-import-{$import->id}")
                            ->label(sprintf(
                                '%s · %s · %s – %s',
                                $import->bank->getLabel(),
                                $import->cashAccount->name,
                                $import->starts_on->format('Y-m-d'),
                                $import->ends_on->format('Y-m-d'),
                            ))
                            ->link()
                            ->color('gray')
                            ->url(BankStatementReview::getUrl(['import' => $import->id])))->all()
                    ),
                ])
                ->visible($imports->isNotEmpty()),
        ]);
    }
}

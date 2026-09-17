<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Filament\Support\AccountingFormFields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                AccountingFormFields::companyId(),
                AccountingFormFields::thirdParty(),
                AccountingFormFields::chartAccount('revenue_account_id', 'Cuenta de ingreso', '4'),
                AccountingFormFields::chartAccount('receivable_account_id', 'Cuenta por cobrar', '13'),
                AccountingFormFields::date('expires_on', 'Vigencia hasta')->default(now()->addDays(30)),
                Repeater::make('lines')
                    ->label('Líneas de la cotización')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')->label('Descripción')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('quantity')->label('Cantidad')->numeric()->minValue(0.01)->step('0.01')->required(),
                        AccountingFormFields::money('unit_price', 'Valor unitario'),
                    ])
                    ->columns(4)
                    ->minItems(1)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Notas / términos comerciales')
                    ->helperText('Opcional. Se imprime en el PDF solo si tiene contenido (ej. "50% anticipo, 50% contra entrega").')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\WithholdingRules;

use App\Filament\Resources\WithholdingRules\Pages\CreateWithholdingRule;
use App\Filament\Resources\WithholdingRules\Pages\EditWithholdingRule;
use App\Filament\Resources\WithholdingRules\Pages\ListWithholdingRules;
use App\Filament\Resources\WithholdingRules\Schemas\WithholdingRuleForm;
use App\Filament\Resources\WithholdingRules\Tables\WithholdingRulesTable;
use App\Models\WithholdingRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WithholdingRuleResource extends Resource
{
    protected static ?string $model = WithholdingRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WithholdingRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WithholdingRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithholdingRules::route('/'),
            'create' => CreateWithholdingRule::route('/create'),
            'edit' => EditWithholdingRule::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CrmOpportunities;

use App\Filament\Resources\CrmOpportunities\Pages\CreateCrmOpportunity;
use App\Filament\Resources\CrmOpportunities\Pages\EditCrmOpportunity;
use App\Filament\Resources\CrmOpportunities\Pages\ListCrmOpportunities;
use App\Filament\Resources\CrmOpportunities\Schemas\CrmOpportunityForm;
use App\Filament\Resources\CrmOpportunities\Tables\CrmOpportunitiesTable;
use App\Models\CrmOpportunity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CrmOpportunityResource extends Resource
{
    protected static ?string $model = CrmOpportunity::class;

    protected static ?string $navigationLabel = 'CRM opportunities';

    protected static ?string $modelLabel = 'opportunity';

    protected static ?string $pluralModelLabel = 'opportunities';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return CrmOpportunityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmOpportunitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmOpportunities::route('/'),
            'create' => CreateCrmOpportunity::route('/create'),
            'edit' => EditCrmOpportunity::route('/{record}/edit'),
        ];
    }
}

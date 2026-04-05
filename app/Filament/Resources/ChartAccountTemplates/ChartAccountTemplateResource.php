<?php

namespace App\Filament\Resources\ChartAccountTemplates;

use App\Filament\Resources\ChartAccountTemplates\Pages\CreateChartAccountTemplate;
use App\Filament\Resources\ChartAccountTemplates\Pages\EditChartAccountTemplate;
use App\Filament\Resources\ChartAccountTemplates\Pages\ListChartAccountTemplates;
use App\Filament\Resources\ChartAccountTemplates\Schemas\ChartAccountTemplateForm;
use App\Filament\Resources\ChartAccountTemplates\Tables\ChartAccountTemplatesTable;
use App\Models\ChartAccountTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ChartAccountTemplateResource extends Resource
{
    protected static ?string $model = ChartAccountTemplate::class;

    protected static ?string $navigationLabel = 'Chart templates';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return ChartAccountTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChartAccountTemplatesTable::configure($table);
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
            'index' => ListChartAccountTemplates::route('/'),
            'create' => CreateChartAccountTemplate::route('/create'),
            'edit' => EditChartAccountTemplate::route('/{record}/edit'),
        ];
    }
}

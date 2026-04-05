<?php

namespace App\Filament\Resources\CrmActivities;

use App\Filament\Resources\CrmActivities\Pages\EditCrmActivity;
use App\Filament\Resources\CrmActivities\Pages\ListCrmActivities;
use App\Filament\Resources\CrmActivities\Schemas\CrmActivityForm;
use App\Filament\Resources\CrmActivities\Tables\CrmActivitiesTable;
use App\Models\CrmActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CrmActivityResource extends Resource
{
    protected static ?string $model = CrmActivity::class;

    protected static ?string $navigationLabel = 'CRM activities';

    protected static ?string $modelLabel = 'CRM activity';

    protected static ?string $pluralModelLabel = 'CRM activities';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return CrmActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmActivitiesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['subject', 'company']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmActivities::route('/'),
            'edit' => EditCrmActivity::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources\JournalLines;

use App\Filament\Resources\JournalLines\Pages\CreateJournalLine;
use App\Filament\Resources\JournalLines\Pages\EditJournalLine;
use App\Filament\Resources\JournalLines\Pages\ListJournalLines;
use App\Filament\Resources\JournalLines\Schemas\JournalLineForm;
use App\Filament\Resources\JournalLines\Tables\JournalLinesTable;
use App\Models\JournalLine;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JournalLineResource extends Resource
{
    protected static ?string $model = JournalLine::class;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public static function form(Schema $schema): Schema
    {
        return JournalLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalLinesTable::configure($table);
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
            'index' => ListJournalLines::route('/'),
            'create' => CreateJournalLine::route('/create'),
            'edit' => EditJournalLine::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\FinancialPositions;

use App\Filament\Resources\FinancialPositions\Pages\CreateFinancialPosition;
use App\Filament\Resources\FinancialPositions\Pages\EditFinancialPosition;
use App\Filament\Resources\FinancialPositions\Pages\ListFinancialPositions;
use App\Filament\Resources\FinancialPositions\Schemas\FinancialPositionForm;
use App\Filament\Resources\FinancialPositions\Tables\FinancialPositionsTable;
use App\Models\FinancialPosition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FinancialPositionResource extends Resource
{
    protected static ?string $model = FinancialPosition::class;

    protected static ?string $navigationLabel = 'Loans & interest';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    public static function form(Schema $schema): Schema
    {
        return FinancialPositionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialPositionsTable::configure($table);
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
            'index' => ListFinancialPositions::route('/'),
            'create' => CreateFinancialPosition::route('/create'),
            'edit' => EditFinancialPosition::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Debtors;

use App\Filament\Resources\Debtors\Pages\CreateDebtor;
use App\Filament\Resources\Debtors\Pages\EditDebtor;
use App\Filament\Resources\Debtors\Pages\ListDebtors;
use App\Filament\Resources\Debtors\Schemas\DebtorForm;
use App\Filament\Resources\Debtors\Tables\DebtorsTable;
use App\Models\Debtor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DebtorResource extends Resource
{
    protected static ?string $model = Debtor::class;

    protected static ?string $navigationLabel = 'Debtors';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return DebtorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebtorsTable::configure($table);
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
            'index' => ListDebtors::route('/'),
            'create' => CreateDebtor::route('/create'),
            'edit' => EditDebtor::route('/{record}/edit'),
        ];
    }
}

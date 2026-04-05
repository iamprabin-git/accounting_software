<?php

namespace App\Filament\Resources\Creditors;

use App\Filament\Resources\Creditors\Pages\CreateCreditor;
use App\Filament\Resources\Creditors\Pages\EditCreditor;
use App\Filament\Resources\Creditors\Pages\ListCreditors;
use App\Filament\Resources\Creditors\Schemas\CreditorForm;
use App\Filament\Resources\Creditors\Tables\CreditorsTable;
use App\Models\Creditor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CreditorResource extends Resource
{
    protected static ?string $model = Creditor::class;

    protected static ?string $navigationLabel = 'Creditors';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function form(Schema $schema): Schema
    {
        return CreditorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditorsTable::configure($table);
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
            'index' => ListCreditors::route('/'),
            'create' => CreateCreditor::route('/create'),
            'edit' => EditCreditor::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CrmContacts;

use App\Filament\Resources\CrmContacts\Pages\CreateCrmContact;
use App\Filament\Resources\CrmContacts\Pages\EditCrmContact;
use App\Filament\Resources\CrmContacts\Pages\ListCrmContacts;
use App\Filament\Resources\CrmContacts\Schemas\CrmContactForm;
use App\Filament\Resources\CrmContacts\Tables\CrmContactsTable;
use App\Models\CrmContact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CrmContactResource extends Resource
{
    protected static ?string $model = CrmContact::class;

    protected static ?string $navigationLabel = 'CRM contacts';

    protected static ?string $modelLabel = 'CRM contact';

    protected static ?string $pluralModelLabel = 'CRM contacts';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    public static function form(Schema $schema): Schema
    {
        return CrmContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmContactsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmContacts::route('/'),
            'create' => CreateCrmContact::route('/create'),
            'edit' => EditCrmContact::route('/{record}/edit'),
        ];
    }
}

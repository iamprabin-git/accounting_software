<?php

namespace App\Filament\Resources\PlatformAdmins;

use App\Filament\Resources\PlatformAdmins\Pages\CreatePlatformAdmin;
use App\Filament\Resources\PlatformAdmins\Pages\EditPlatformAdmin;
use App\Filament\Resources\PlatformAdmins\Pages\ListPlatformAdmins;
use App\Filament\Resources\PlatformAdmins\Schemas\PlatformAdminForm;
use App\Filament\Resources\PlatformAdmins\Tables\PlatformAdminsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlatformAdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Platform admins';

    protected static ?string $modelLabel = 'platform admin';

    protected static ?string $pluralModelLabel = 'platform admins';

    protected static string|UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', User::ROLE_ADMIN);
    }

    public static function form(Schema $schema): Schema
    {
        return PlatformAdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformAdminsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformAdmins::route('/'),
            'create' => CreatePlatformAdmin::route('/create'),
            'edit' => EditPlatformAdmin::route('/{record}/edit'),
        ];
    }
}

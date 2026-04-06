<?php

namespace App\Filament\Resources\PlatformAdmins\Pages;

use App\Filament\Resources\PlatformAdmins\PlatformAdminResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformAdmins extends ListRecords
{
    protected static string $resource = PlatformAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\CrmAccounts\Pages;

use App\Filament\Resources\CrmAccounts\CrmAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmAccounts extends ListRecords
{
    protected static string $resource = CrmAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

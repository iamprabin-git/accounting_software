<?php

namespace App\Filament\Resources\Debtors\Pages;

use App\Filament\Resources\Debtors\DebtorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDebtors extends ListRecords
{
    protected static string $resource = DebtorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

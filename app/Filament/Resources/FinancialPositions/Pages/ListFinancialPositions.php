<?php

namespace App\Filament\Resources\FinancialPositions\Pages;

use App\Filament\Resources\FinancialPositions\FinancialPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialPositions extends ListRecords
{
    protected static string $resource = FinancialPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

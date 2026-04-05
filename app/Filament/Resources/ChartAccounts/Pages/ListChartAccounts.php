<?php

namespace App\Filament\Resources\ChartAccounts\Pages;

use App\Filament\Resources\ChartAccounts\ChartAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChartAccounts extends ListRecords
{
    protected static string $resource = ChartAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

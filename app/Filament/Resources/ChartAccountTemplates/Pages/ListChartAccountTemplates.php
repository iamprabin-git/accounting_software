<?php

namespace App\Filament\Resources\ChartAccountTemplates\Pages;

use App\Filament\Resources\ChartAccountTemplates\ChartAccountTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChartAccountTemplates extends ListRecords
{
    protected static string $resource = ChartAccountTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

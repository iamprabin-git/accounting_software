<?php

namespace App\Filament\Resources\ChartAccountTemplates\Pages;

use App\Filament\Resources\ChartAccountTemplates\ChartAccountTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChartAccountTemplate extends EditRecord
{
    protected static string $resource = ChartAccountTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ChartAccounts\Pages;

use App\Filament\Resources\ChartAccounts\ChartAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChartAccount extends EditRecord
{
    protected static string $resource = ChartAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

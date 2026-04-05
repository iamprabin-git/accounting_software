<?php

namespace App\Filament\Resources\CrmOpportunities\Pages;

use App\Filament\Resources\CrmOpportunities\CrmOpportunityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmOpportunities extends ListRecords
{
    protected static string $resource = CrmOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
